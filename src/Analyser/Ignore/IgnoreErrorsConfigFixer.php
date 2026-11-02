<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Nette\DI\Helpers;
use Nette\InvalidArgumentException;
use Nette\Neon\Exception as NeonException;
use Nette\Neon\Lexer as NeonLexer;
use Nette\Neon\Node as NeonNode;
use Nette\Neon\Node\ArrayItemNode as NeonArrayItemNode;
use Nette\Neon\Node\ArrayNode as NeonArrayNode;
use Nette\Neon\Node\LiteralNode as NeonLiteralNode;
use Nette\Neon\Parser as NeonParser;
use Nette\Neon\TokenStream as NeonTokenStream;
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
use PHPStan\DependencyInjection\NeonAdapter;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Neon\Printer as NeonPrinter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function array_search;
use function array_slice;
use function array_values;
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
use function str_replace;
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
 *
 * Baselines can have tens of thousands of entries and each fixed error needs its own diff, which covers
 * only the changed lines. In a PHP config file, a fix is an edit of the source range of the parsed node,
 * printing the whole file with the format-preserving printer for every fix would make fixing a large baseline
 * take hours. A NEON config file is printed with PHPStan\Neon\Printer, which copies the unchanged parts
 * of the file as they are.
 *
 * @phpstan-import-type ExpandedIgnoredErrorData from IgnoredErrorHelperResult
 * @phpstan-type PhpConfigEntry = array{
 *     file: string,
 *     values: array<string, mixed>,
 *     format: 'php',
 *     items: array<string, Node\ArrayItem>,
 *     removableNode: Node|null,
 * }
 * @phpstan-type NeonConfigEntry = array{
 *     file: string,
 *     values: array<string, mixed>,
 *     format: 'neon',
 *     items: array<string, NeonArrayItemNode>,
 *     path: non-empty-list<NeonNode>,
 *     entryValue: NeonArrayNode,
 * }
 * @phpstan-type ConfigEntry = PhpConfigEntry|NeonConfigEntry
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

	/** @var array<string, array{tokens: NeonTokenStream, root: NeonNode, codeWithoutCr: string, crlf: bool, mixedLineBreaks: bool}> parsed NEON config files */
	private array $neonFiles = [];

	/**
	 * @param list<string> $allConfigFiles
	 */
	public function __construct(
		#[AutowiredParameter(ref: '@currentPhpVersionPhpParser')]
		private Parser $parser,
		private PackageDependencyResolver $packageDependencyResolver,
		private NeonPrinter $neonPrinter,
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
		$neonChangesByFile = [];
		foreach ($this->findEntries($ignoreError) as [$entry, $listField, $position]) {
			if ($entry['format'] === 'neon') {
				if ($listField === null || $position === null) {
					$neonChangesByFile[$entry['file']][] = [$entry['path'], null];
					continue;
				}

				$listItemNode = $entry['items'][$listField];
				$listNode = $listItemNode->value;
				if (!$listNode instanceof NeonArrayNode || count($listNode->items) !== count($entry['values'][$listField])) {
					return [];
				}

				$neonChangesByFile[$entry['file']][] = [[...$entry['path'], $entry['entryValue'], $listItemNode, $listNode, array_values($listNode->items)[$position]], null];
				continue;
			}

			$code = $this->files[$entry['file']]['code'];
			if ($listField === null || $position === null) {
				if ($entry['removableNode'] === null) {
					return [];
				}

				$editsByFile[$entry['file']][] = $this->createRemovalEdit($code, $entry['removableNode']);
				continue;
			}

			$listNode = $entry['items'][$listField]->value;
			if (!$listNode instanceof Expr\Array_ || count($listNode->items) !== count($entry['values'][$listField])) {
				return [];
			}

			// The edit replaces the whole list, so that removing another item of it conflicts.
			// Otherwise all items could be removed in one round, leaving an entry without messages.
			// The next round removes the whole entry once its last item is unmatched.
			// The NEON printer prints the whole list too.
			$itemEdit = $this->createRemovalEdit($code, $listNode->items[$position]);
			$listStart = $listNode->getStartFilePos();
			$listEnd = $listNode->getEndFilePos() + 1;
			$editsByFile[$entry['file']][] = [
				'start' => $listStart,
				'end' => $listEnd,
				'replacement' => substr($code, $listStart, $itemEdit['start'] - $listStart) . substr($code, $itemEdit['end'], $listEnd - $itemEdit['end']),
				'line' => $listNode->getStartLine(),
			];
		}

		foreach ($neonChangesByFile as $file => $changes) {
			$edit = $this->createNeonEdit($file, $changes);
			if ($edit === null) {
				return [];
			}

			$editsByFile[$file][] = $edit;
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

		[$entry, $listField] = $entries[0];
		if (
			$listField !== null
			// another entry of the same error is somewhere this class does not look into
			|| ($entry['values']['count'] ?? null) !== ($ignoreError['count'] ?? null)
		) {
			return [];
		}

		if ($entry['format'] === 'neon') {
			$countItem = $entry['items']['count'] ?? null;
			if ($countItem === null) {
				return [];
			}

			$edit = $this->createNeonEdit($entry['file'], [
				[[...$entry['path'], $entry['entryValue'], $countItem, $countItem->value], new NeonLiteralNode($count)],
			]);

			return $edit !== null ? $this->createDiffs([$entry['file'] => [$edit]]) : [];
		}

		$countItem = $entry['items']['count'] ?? null;
		if ($countItem === null) {
			return [];
		}

		$countNode = $countItem->value;

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
	 * @return list<array{ConfigEntry, string|null, int|null}> the entry, and the list field and position of the item
	 */
	private function findEntries(array $ignoreError): array
	{
		$path = $ignoreError['realPath'] ?? $ignoreError['path'] ?? null;
		if ($path === null) {
			return [];
		}

		$found = [];
		foreach ($this->getEntriesByPath()[$this->normalizePath($path)] ?? [] as $entry) {
			$listField = null;
			$position = null;
			foreach (self::MATCHED_FIELDS as $field => $matchedListField) {
				$expected = $ignoreError[$field] ?? null;
				if ($expected === null) {
					if (array_key_exists($field, $entry['values']) || array_key_exists($matchedListField, $entry['values'])) {
						continue 2;
					}
					continue;
				}

				if (($entry['values'][$field] ?? null) === $expected) {
					continue;
				}

				$list = $entry['values'][$matchedListField] ?? null;
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

				$position = array_search($index, array_keys($list), true);
				if ($position === false) {
					return [];
				}
				$listField = $matchedListField;
			}

			$found[] = [$entry, $listField, $position];
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

			$entries = str_ends_with($configFile, '.php') ? $this->findPhpEntries($configFile) : $this->findNeonEntries($configFile);
			foreach ($entries as $entry) {
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

		$parameters = $this->getParameters();

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
				'format' => 'php',
				'items' => $items,
				'removableNode' => $removableNodes[spl_object_id($node)] ?? null,
			];
		}

		return $entries;
	}

	/**
	 * Entries in the ignoreErrors parameter, which can be removed from its list.
	 *
	 * @return list<NeonConfigEntry>
	 */
	private function findNeonEntries(string $file): array
	{
		try {
			$code = FileReader::read($file);
			$tokens = (new NeonLexer())->tokenize($code);
			$root = (new NeonParser())->parse($tokens);
		} catch (CouldNotReadFileException | NeonException) {
			return [];
		}

		$parametersItem = self::findNeonItem($root, 'parameters');
		if ($parametersItem === null) {
			return [];
		}
		$ignoreErrorsItem = self::findNeonItem($parametersItem->value, 'ignoreErrors');
		if ($ignoreErrorsItem === null || !$ignoreErrorsItem->value instanceof NeonArrayNode) {
			return [];
		}

		$this->files[$file] = ['code' => $code, 'hash' => hash('sha256', $code)];
		$codeWithoutCr = str_replace("\r", '', $code);
		$this->neonFiles[$file] = [
			'tokens' => $tokens,
			'root' => $root,
			'codeWithoutCr' => $codeWithoutCr,
			// nette/neon removes \r, it is put back when every line break had it
			'crlf' => $codeWithoutCr !== $code,
			'mixedLineBreaks' => $codeWithoutCr !== $code && str_replace("\n", "\r\n", $codeWithoutCr) !== $code,
		];

		$parameters = $this->getParameters();
		// relative paths are resolved like when the config file is loaded
		$adapter = new NeonAdapter(['[parameters][ignoreErrors][][path]', '[parameters][ignoreErrors][][paths][]'], $parameters);
		$entries = [];
		foreach ($ignoreErrorsItem->value->items as $entryItem) {
			if (!$entryItem->value instanceof NeonArrayNode) {
				continue;
			}

			$items = [];
			foreach ($entryItem->value->items as $item) {
				$key = $item->key !== null ? $item->key->toValue() : null;
				if (!is_string($key) || !in_array($key, self::ENTRY_KEYS, true)) {
					continue 2;
				}

				$items[$key] = $item;
			}

			try {
				$processed = $adapter->process(['parameters' => ['ignoreErrors' => [$entryItem->value->toValue()]]], '', $file);
				// %% is escaped % in parameters, and other parameters can be used as well
				$values = Helpers::expand($processed['parameters']['ignoreErrors'][0], $parameters);
			} catch (InvalidArgumentException) {
				continue;
			}

			if (!is_array($values) || !isset($values['path']) || (isset($values['count']) && !is_int($values['count']))) {
				continue;
			}

			$entries[] = [
				'file' => $file,
				'values' => $values,
				'format' => 'neon',
				'items' => $items,
				'path' => [$root, $parametersItem, $parametersItem->value, $ignoreErrorsItem, $ignoreErrorsItem->value, $entryItem],
				'entryValue' => $entryItem->value,
			];
		}

		return $entries;
	}

	private static function findNeonItem(NeonNode $node, string $key): ?NeonArrayItemNode
	{
		if (!$node instanceof NeonArrayNode) {
			return null;
		}

		foreach ($node->items as $item) {
			if ($item->key !== null && $item->key->toValue() === $key) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Prints the NEON file with the changes and turns it into one edit of its changed part.
	 *
	 * @param list<array{non-empty-list<NeonNode>, NeonNode|null}> $changes path from the root to the changed node,
	 *                                                                      and its replacement, or null to remove it
	 * @return Edit|null
	 */
	private function createNeonEdit(string $file, array $changes): ?array
	{
		['tokens' => $tokens, 'root' => $root, 'codeWithoutCr' => $codeWithoutCr, 'crlf' => $crlf, 'mixedLineBreaks' => $mixedLineBreaks] = $this->neonFiles[$file];
		if ($mixedLineBreaks) {
			return null;
		}
		$newRoot = self::changeNeonTree($root, $changes);
		if ($newRoot === null) {
			return null;
		}

		$printedChanges = $this->neonPrinter->printChanges($newRoot, $root, $tokens);
		if ($printedChanges === []) {
			return null;
		}

		// one edit from the first change to the last one
		$start = $printedChanges[0]['start'];
		$replacement = '';
		$offset = $start;
		foreach ($printedChanges as $printedChange) {
			$replacement .= substr($codeWithoutCr, $offset, $printedChange['start'] - $offset) . $printedChange['replacement'];
			$offset = $printedChange['end'];
		}
		$end = $offset;
		if ($crlf) {
			$start += substr_count($codeWithoutCr, "\n", length: $start);
			$end += substr_count($codeWithoutCr, "\n", length: $end);
			$replacement = str_replace("\n", "\r\n", $replacement);
		}

		return [
			'start' => $start,
			'end' => $end,
			'replacement' => $replacement,
			'line' => substr_count($this->files[$file]['code'], "\n", length: $start) + 1,
		];
	}

	/**
	 * Replaces the changed nodes and clones their ancestors, the original tree stays as it is.
	 * The clones keep the token positions of the original nodes, so the printer knows what they stand for.
	 *
	 * @param list<array{non-empty-list<NeonNode>, NeonNode|null}> $changes
	 */
	private static function changeNeonTree(NeonNode $root, array $changes): ?NeonNode
	{
		/** @var array<int, NeonNode> $clones clones by spl_object_id() of the original node */
		$clones = [];
		foreach ($changes as [$path, $replacement]) {
			$target = array_pop($path);
			if ($path === []) {
				return null;
			}

			$parentClones = [];
			foreach ($path as $node) {
				$parentClones[] = $clones[spl_object_id($node)] ??= clone $node;
			}
			$children = [...array_slice($parentClones, 1), $replacement];
			$originalChildren = [...array_slice($path, 1), $target];
			foreach ($parentClones as $i => $parentClone) {
				$originalChild = $originalChildren[$i];
				if (!self::replaceNeonChild($parentClone, [$originalChild, $clones[spl_object_id($originalChild)] ?? $originalChild], $children[$i])) {
					return null;
				}
			}
		}

		return $clones[spl_object_id($root)] ?? $root;
	}

	/**
	 * @param array{NeonNode, NeonNode} $oldChildren the original child and the node in its place
	 */
	private static function replaceNeonChild(NeonNode $parent, array $oldChildren, ?NeonNode $newChild): bool
	{
		if ($parent instanceof NeonArrayNode) {
			foreach ($parent->items as $i => $item) {
				if (!in_array($item, $oldChildren, true)) {
					continue;
				}

				if ($newChild === null) {
					unset($parent->items[$i]);
					$parent->items = array_values($parent->items);
					return true;
				}
				if (!$newChild instanceof NeonArrayItemNode) {
					return false;
				}

				$parent->items[$i] = $newChild;
				return true;
			}

			return false;
		}

		if ($parent instanceof NeonArrayItemNode && $newChild !== null) {
			if (in_array($parent->value, $oldChildren, true)) {
				$parent->value = $newChild;
				return true;
			}
			if ($parent->key !== null && in_array($parent->key, $oldChildren, true)) {
				$parent->key = $newChild;
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getParameters(): array
	{
		return [
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => Environment::getCleanedArray(),
		];
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
