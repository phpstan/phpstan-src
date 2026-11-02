<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Nette\DI\Helpers;
use Nette\InvalidArgumentException;
use Nette\Utils\Strings;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Error as PhpParserError;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\Command\Environment;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_search;
use function count;
use function dirname;
use function hash;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function rtrim;
use function spl_object_id;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strspn;
use function substr;
use function substr_count;
use function trim;
use function usort;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Fixes ignoreErrors entries in the config files: removes an entry that matched no error,
 * and lowers the count of an entry whose error occurred fewer times. A higher count is not fixed,
 * it would ignore more errors - that is what --generate-baseline is for.
 *
 * The entry is looked for in the whole config cascade, except for files of installed Composer packages.
 * The ignoreErrors parameter is merged from all of them, so the entry can come from more than one file.
 * Only PHP config files, like PHP baselines, are fixed so far.
 *
 * Baselines can have tens of thousands of entries and each fixed error needs its own diff,
 * so a fix is an edit of the source range of the parsed node and its diff covers only the changed lines.
 * Printing the whole file for every fix would make fixing a large baseline take hours.
 *
 * @phpstan-import-type ExpandedIgnoredErrorData from IgnoredErrorHelperResult
 * @phpstan-type ConfigEntry = array{
 *     file: string,
 *     values: array<string, mixed>,
 *     items: array<string, Node\ArrayItem>,
 *     removableNode: Node|null,
 * }
 * @phpstan-type Edit = array{start: int, end: int, replacement: string, line: int}
 */
#[AutowiredService]
final class IgnoreErrorsConfigFixer
{

	private const ENTRY_KEYS = ['message', 'messages', 'rawMessage', 'rawMessages', 'identifier', 'identifiers', 'path', 'paths', 'count', 'reportUnmatched'];

	private const MATCHED_FIELDS = [
		'message' => 'messages',
		'rawMessage' => 'rawMessages',
		'identifier' => 'identifiers',
	];

	/** @var array<string, list<ConfigEntry>>|null entries by normalized path */
	private ?array $entriesByPath = null;

	/** @var array<string, array{code: string, hash: string}> contents of the config files the entries were found in */
	private array $files = [];

	/**
	 * @param list<string> $allConfigFiles
	 */
	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionPhpParser')]
		private Parser $parser,
		private PackageDependencyResolver $packageDependencyResolver,
		private FileHelper $fileHelper,
		#[AutowiredParameter]
		private array $allConfigFiles,
		#[AutowiredParameter]
		private string $rootDir,
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
	)
	{
	}

	/**
	 * @param ExpandedIgnoredErrorData $ignoreError
	 * @return list<FixedErrorDiff>
	 */
	public function removeUnmatched(array $ignoreError): array
	{
		$editsByFile = [];
		foreach ($this->findEntries($ignoreError) as [$entry, $listItem, $listNode]) {
			$code = $this->files[$entry['file']]['code'];
			if ($listItem === null || $listNode === null) {
				if ($entry['removableNode'] === null) {
					return [];
				}

				$editsByFile[$entry['file']][] = $this->createRemovalEdit($code, $entry['removableNode']);
				continue;
			}

			// The edit replaces the whole list, so that removing another item of it conflicts.
			// Otherwise all items could be removed in one round, leaving an entry without messages.
			// The next round removes the whole entry once its last item is unmatched.
			$itemEdit = $this->createRemovalEdit($code, $listItem);
			$listStart = $listNode->getStartFilePos();
			$listEnd = $listNode->getEndFilePos() + 1;
			$editsByFile[$entry['file']][] = [
				'start' => $listStart,
				'end' => $listEnd,
				'replacement' => substr($code, $listStart, $itemEdit['start'] - $listStart) . substr($code, $itemEdit['end'], $listEnd - $itemEdit['end']),
				'line' => $listNode->getStartLine(),
			];
		}

		return $this->createDiffs($editsByFile);
	}

	/**
	 * @param ExpandedIgnoredErrorData $ignoreError
	 * @return list<FixedErrorDiff>
	 */
	public function lowerCount(array $ignoreError, int $count): array
	{
		$entries = $this->findEntries($ignoreError);
		if (count($entries) !== 1) {
			return [];
		}

		[$entry, $listItem] = $entries[0];
		if (
			$listItem !== null
			|| !isset($entry['items']['count'])
			// another entry of the same error is somewhere this class does not look into, like a NEON file
			|| $entry['values']['count'] !== ($ignoreError['count'] ?? null)
		) {
			return [];
		}

		$countNode = $entry['items']['count']->value;

		return $this->createDiffs([
			$entry['file'] => [[
				'start' => $countNode->getStartFilePos(),
				'end' => $countNode->getEndFilePos() + 1,
				'replacement' => (string) $count,
				'line' => $countNode->getStartLine(),
			]],
		]);
	}

	/**
	 * Removes the node, with the comma separating it from the other items when it is an array item,
	 * and with its lines when nothing else is on them.
	 *
	 * @return Edit
	 */
	private function createRemovalEdit(string $code, Node $node): array
	{
		$start = $node->getStartFilePos();
		$end = $node->getEndFilePos() + 1;
		$line = $node->getStartLine();
		if ($node instanceof Node\ArrayItem) {
			$afterEnd = $end + strspn($code, " \t\r\n", $end);
			if (($code[$afterEnd] ?? '') === ',') {
				$end = $afterEnd + 1;
				$end += strspn($code, " \t", $end);
			} else {
				// the last item without a trailing comma, the comma before it goes
				$beforeStart = $start - 1;
				while ($beforeStart >= 0 && in_array($code[$beforeStart], [' ', "\t", "\r", "\n"], true)) {
					$beforeStart--;
				}
				if ($beforeStart >= 0 && $code[$beforeStart] === ',') {
					$line -= substr_count($code, "\n", $beforeStart, $start - $beforeStart);
					$start = $beforeStart;
				}
			}
		}

		$lineBreak = $start > 0 ? strrpos($code, "\n", $start - 1 - strlen($code)) : false;
		$lineStart = $lineBreak === false ? 0 : $lineBreak + 1;
		$lineBreak = strpos($code, "\n", $end - 1);
		$lineEnd = $lineBreak === false ? strlen($code) : $lineBreak + 1;
		if (
			trim(substr($code, $lineStart, $start - $lineStart)) === ''
			&& trim(substr($code, $end, $lineEnd - $end)) === ''
		) {
			$start = $lineStart;
			$end = $lineEnd;
		}

		return ['start' => $start, 'end' => $end, 'replacement' => '', 'line' => $line];
	}

	/**
	 * @param array<string, list<Edit>> $editsByFile
	 * @return list<FixedErrorDiff>
	 */
	private function createDiffs(array $editsByFile): array
	{
		$diffs = [];
		foreach ($editsByFile as $file => $edits) {
			$diff = $this->createDiff($this->files[$file]['code'], $edits);
			if ($diff === null) {
				// the changes are applied together or not at all
				return [];
			}

			$diffs[] = new FixedErrorDiff($file, $this->files[$file]['hash'], $diff);
		}

		return $diffs;
	}

	/**
	 * A unified diff without context lines, the lines of each edit make one hunk.
	 *
	 * @param list<Edit> $edits
	 */
	private function createDiff(string $code, array $edits): ?string
	{
		usort($edits, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

		$diff = '';
		$previousLineEnd = 0;
		$lineOffset = 0;
		foreach ($edits as $edit) {
			$lineBreak = $edit['start'] > 0 ? strrpos($code, "\n", $edit['start'] - 1 - strlen($code)) : false;
			$lineStart = $lineBreak === false ? 0 : $lineBreak + 1;
			$lineBreak = strpos($code, "\n", $edit['end'] > $edit['start'] ? $edit['end'] - 1 : $edit['start']);
			$lineEnd = $lineBreak === false ? strlen($code) : $lineBreak + 1;
			if ($lineStart < $previousLineEnd) {
				return null;
			}
			$previousLineEnd = $lineEnd;

			$before = substr($code, $lineStart, $edit['start'] - $lineStart);
			$after = substr($code, $edit['end'], $lineEnd - $edit['end']);
			$newCode = $before . $edit['replacement'] . $after;

			$oldLines = self::splitStringByLines(substr($code, $lineStart, $lineEnd - $lineStart));
			$newLines = self::splitStringByLines($newCode);
			$diff .= sprintf('@@ -%d,%d +%d,%d @@', $edit['line'], count($oldLines), $edit['line'] + $lineOffset, count($newLines)) . "\n";
			foreach ($oldLines as $oldLine) {
				// the last line of a file without a line break, the next diff line must not continue it
				$diff .= '-' . rtrim($oldLine, "\r\n") . "\n";
			}
			$diff .= implode('', array_map(static fn (string $newLine): string => '+' . $newLine, $newLines));
			$lineOffset += count($newLines) - count($oldLines);
		}

		return $diff;
	}

	/**
	 * Entries the expanded entry was made of. The list item is set when the entry matched through a list
	 * like `messages` that has other items too, only that item can then be removed.
	 *
	 * @param ExpandedIgnoredErrorData $ignoreError
	 * @return list<array{ConfigEntry, Node\ArrayItem|null, Expr\Array_|null}>
	 */
	private function findEntries(array $ignoreError): array
	{
		$path = $ignoreError['realPath'] ?? $ignoreError['path'] ?? null;
		if ($path === null) {
			return [];
		}

		$found = [];
		foreach ($this->getEntriesByPath()[$this->normalizePath($path)] ?? [] as $entry) {
			$listItem = null;
			$listNode = null;
			foreach (self::MATCHED_FIELDS as $field => $listField) {
				$expected = $ignoreError[$field] ?? null;
				if ($expected === null) {
					if (array_key_exists($field, $entry['values']) || array_key_exists($listField, $entry['values'])) {
						continue 2;
					}
					continue;
				}

				if (($entry['values'][$field] ?? null) === $expected) {
					continue;
				}

				$list = $entry['values'][$listField] ?? null;
				if (!is_array($list)) {
					continue 2;
				}

				$index = array_search($expected, $list, true);
				if ($index === false) {
					continue 2;
				}

				if (count($list) === 1) {
					continue;
				}

				$listNode = $entry['items'][$listField]->value;
				if (!$listNode instanceof Expr\Array_ || count($listNode->items) !== count($list)) {
					return [];
				}

				$position = array_search($index, array_keys($list), true);
				if ($position === false) {
					return [];
				}

				$listItem = $listNode->items[$position];
			}

			$found[] = [$entry, $listItem, $listNode];
		}

		return $found;
	}

	/**
	 * @return array<string, list<ConfigEntry>>
	 */
	private function getEntriesByPath(): array
	{
		if ($this->entriesByPath !== null) {
			return $this->entriesByPath;
		}

		$this->entriesByPath = [];
		foreach ($this->allConfigFiles as $configFile) {
			if (str_starts_with($configFile, 'phar://') || $this->packageDependencyResolver->isFileOfInstalledPackage($configFile)) {
				continue;
			}

			// NEON config files are not fixed yet
			if (!str_ends_with($configFile, '.php')) {
				continue;
			}

			foreach ($this->findPhpEntries($configFile) as $entry) {
				$path = $entry['values']['path'] ?? null;
				if (!is_string($path)) {
					continue;
				}

				$this->entriesByPath[$this->normalizePath($path)][] = $entry;
			}
		}

		return $this->entriesByPath;
	}

	/**
	 * Every array that looks like an ignoreErrors entry, wherever it is in the file. Such an array is removable
	 * when it is an item of another array, or when it is added in a statement like `$ignoreErrors[] = [...];`.
	 *
	 * @return list<ConfigEntry>
	 */
	private function findPhpEntries(string $file): array
	{
		try {
			$code = FileReader::read($file);
			$stmts = $this->parser->parse($code);
		} catch (CouldNotReadFileException | PhpParserError) {
			return [];
		}
		if ($stmts === null) {
			return [];
		}

		$this->files[$file] = ['code' => $code, 'hash' => hash('sha256', $code)];

		$evaluator = new ConstExprEvaluator(static function (Expr $expr) use ($file): string {
			if ($expr instanceof Node\Scalar\MagicConst\Dir) {
				return dirname($file);
			}
			if ($expr instanceof Node\Scalar\MagicConst\File) {
				return $file;
			}

			throw new ConstExprEvaluationException();
		});

		$parameters = [
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => Environment::getCleanedArray(),
		];

		$removableNodes = [];
		$entries = [];
		// in the order of the source, so the parent of an array comes before it
		foreach ((new NodeFinder())->find($stmts, static fn (Node $node): bool => $node instanceof Expr\Array_ || $node instanceof Node\Stmt\Expression) as $node) {
			if ($node instanceof Node\Stmt\Expression) {
				if (
					$node->expr instanceof Expr\Assign
					&& $node->expr->var instanceof Expr\ArrayDimFetch
					&& $node->expr->var->dim === null
				) {
					$removableNodes[spl_object_id($node->expr->expr)] = $node;
				}
				continue;
			}
			if (!$node instanceof Expr\Array_) {
				continue;
			}

			foreach ($node->items as $item) {
				$removableNodes[spl_object_id($item->value)] = $item;
			}

			$items = [];
			$values = [];
			foreach ($node->items as $item) {
				if (!$item->key instanceof Node\Scalar\String_ || !in_array($item->key->value, self::ENTRY_KEYS, true)) {
					continue 2;
				}

				try {
					// %% is escaped % in parameters, and other parameters can be used as well
					$value = Helpers::expand($evaluator->evaluateDirectly($item->value), $parameters);
				} catch (ConstExprEvaluationException | InvalidArgumentException) {
					continue 2;
				}

				$items[$item->key->value] = $item;
				$values[$item->key->value] = $value;
			}

			if (!isset($values['path']) || (isset($values['count']) && !is_int($values['count']))) {
				continue;
			}

			$entries[] = [
				'file' => $file,
				'values' => $values,
				'items' => $items,
				'removableNode' => $removableNodes[spl_object_id($node)] ?? null,
			];
		}

		return $entries;
	}

	private function normalizePath(string $path): string
	{
		return $this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path));
	}

	/**
	 * @return string[]
	 */
	private static function splitStringByLines(string $input): array
	{
		return Strings::split($input, '/(.*\R)/', PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	}

}
