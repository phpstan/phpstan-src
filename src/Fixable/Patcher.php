<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Nette\Utils\Strings;
use PhpMerge\internal\Hunk;
use PhpMerge\internal\Line;
use PhpMerge\MergeConflict;
use PhpMerge\PhpMerge;
use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use ReflectionClass;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function array_map;
use function count;
use function hash;
use function implode;
use function str_starts_with;
use function substr;
use const PHP_VERSION_ID;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

#[AutowiredService]
final class Patcher
{

	private Differ $differ;

	public function __construct()
	{
		$this->differ = new Differ(new UnifiedDiffOutputBuilder());
	}

	/**
	 * @param FixedErrorDiff[] $diffs
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	public function applyDiffs(string $fileName, array $diffs): string
	{
		$fileContents = FileReader::read($fileName);
		$fileHash = hash('sha256', $fileContents);
		$diffHunks = [];
		foreach ($diffs as $diff) {
			if ($diff->originalHash !== $fileHash) {
				throw new FileChangedException();
			}

			$diffHunks[] = Hunk::createArray(Line::createArray($this->reconstructFullDiff($fileContents, $diff->diff)));
		}

		if (count($diffHunks) === 0) {
			return $fileContents;
		}

		$baseLines = Line::createArray(array_map(
			static fn ($l) => [$l, Differ::OLD],
			self::splitStringByLines($fileContents),
		));

		$refMerge = new ReflectionClass(PhpMerge::class);
		$refMergeMethod = $refMerge->getMethod('mergeHunks');
		if (PHP_VERSION_ID < 80100) {
			$refMergeMethod->setAccessible(true);
		}

		$result = Line::createArray(array_map(
			static fn ($l) => [$l, Differ::OLD],
			$refMergeMethod->invokeArgs(null, [
				$baseLines,
				$diffHunks[0],
				[],
			]),
		));

		for ($i = 0; $i < count($diffHunks); $i++) {
			/** @var MergeConflict[] $conflicts */
			$conflicts = [];
			$merged = $refMergeMethod->invokeArgs(null, [
				$baseLines,
				Hunk::createArray(Line::createArray($this->differ->diffToArray($fileContents, implode('', array_map(static fn ($l) => $l->getContent(), $result))))),
				$diffHunks[$i],
				&$conflicts,
			]);
			if (count($conflicts) > 0) {
				throw new MergeConflictException();
			}

			$result = Line::createArray(array_map(
				static fn ($l) => [$l, Differ::OLD],
				$merged,
			));

		}

		return implode('', array_map(static fn ($l) => $l->getContent(), $result));
	}

	/**
	 * @return array<array{mixed, Differ::OLD|Differ::ADDED|Differ::REMOVED}>
	 */
	private function reconstructFullDiff(string $originalText, string $unifiedDiff): array
	{
		$originalLines = self::splitStringByLines($originalText);
		$diffLines = self::splitStringByLines($unifiedDiff);
		$result = [];

		$origLineNo = 0;
		$diffPos = 0;

		while ($diffPos < count($diffLines)) {
			$line = $diffLines[$diffPos];

			$matches = Strings::match($line, '/^@@ -(\d+),?(\d*) \+(\d+),?(\d*) @@/');
			if ($matches !== null) {
				// Parse hunk header
				$origStart = (int) $matches[1] - 1; // 0-based
				$diffPos++;

				// Emit kept lines before hunk
				while ($origLineNo < $origStart) {
					$result[] = [$originalLines[$origLineNo], Differ::OLD];
					$origLineNo++;
				}

				// Process hunk
				while ($diffPos < count($diffLines)) {
					$line = $diffLines[$diffPos];
					if (str_starts_with($line, '@@')) {
						break; // next hunk
					}

					$prefix = $line[0] ?? '';
					$content = substr($line, 1);

					if ($prefix === ' ') {
						$result[] = [$content, Differ::OLD];
						$origLineNo++;
					} elseif ($prefix === '-') {
						$result[] = [$content, Differ::REMOVED];
						$origLineNo++;
					} elseif ($prefix === '+') {
						$result[] = [$content, Differ::ADDED];
					}

					$diffPos++;
				}
			} else {
				$diffPos++;
			}
		}

		// Emit remaining lines as kept
		while ($origLineNo < count($originalLines)) {
			$result[] = [$originalLines[$origLineNo], Differ::OLD];
			$origLineNo++;
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	private static function splitStringByLines(string $input): array
	{
		return Strings::split($input, '/(.*\R)/', PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	}

}
