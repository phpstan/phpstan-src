<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Nette\Utils\Strings;
use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use function array_splice;
use function count;
use function hash;
use function implode;
use function intdiv;
use function str_starts_with;
use function substr;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Applies unified diffs that were all computed against the same file contents.
 *
 * Each diff is reduced to its change runs (lines removed from the original file
 * and lines inserted in their place). Runs are spliced into the original file
 * in a single pass, so the cost is linear in the size of the file and the diffs.
 * A diff whose runs overlap a run of an earlier diff is skipped as a whole.
 */
#[AutowiredService]
final class Patcher
{

	/**
	 * @param FixedErrorDiff[] $diffs
	 * @return array{contents: string, skippedCount: int}
	 * @throws FileChangedException
	 */
	public function applyDiffs(string $fileName, array $diffs): array
	{
		$fileContents = FileReader::read($fileName);
		$fileHash = hash('sha256', $fileContents);
		foreach ($diffs as $diff) {
			if ($diff->originalHash !== $fileHash) {
				throw new FileChangedException();
			}
		}

		/** @var list<array{int, int, list<string>}> $acceptedRuns sorted by start, non-overlapping */
		$acceptedRuns = [];
		$skippedCount = 0;
		foreach ($diffs as $diff) {
			$insertions = [];
			foreach (self::parseChangeRuns($diff->diff) as $run) {
				$index = self::findInsertionIndex($acceptedRuns, $run);
				if ($index === null) {
					$skippedCount++;
					continue 2;
				}
				if (isset($acceptedRuns[$index]) && $acceptedRuns[$index] === $run) {
					// another diff makes the exact same change
					continue;
				}
				$insertions[] = [$index, $run];
			}

			// runs of a single diff are ordered and non-overlapping,
			// inserting from the last one keeps the earlier indexes valid
			for ($i = count($insertions) - 1; $i >= 0; $i--) {
				array_splice($acceptedRuns, $insertions[$i][0], 0, [$insertions[$i][1]]);
			}
		}

		$baseLines = self::splitStringByLines($fileContents);
		$result = [];
		$position = 0;
		foreach ($acceptedRuns as [$start, $removedCount, $addedLines]) {
			for (; $position < $start; $position++) {
				$result[] = $baseLines[$position];
			}
			foreach ($addedLines as $addedLine) {
				$result[] = $addedLine;
			}
			$position += $removedCount;
		}
		for ($baseLinesCount = count($baseLines); $position < $baseLinesCount; $position++) {
			$result[] = $baseLines[$position];
		}

		return [
			'contents' => implode('', $result),
			'skippedCount' => $skippedCount,
		];
	}

	/**
	 * Returns the index in $acceptedRuns where $run belongs, or null when it conflicts with an accepted run.
	 * When the run at the returned index is identical to $run, the change is already accepted.
	 *
	 * @param list<array{int, int, list<string>}> $acceptedRuns
	 * @param array{int, int, list<string>} $run
	 */
	private static function findInsertionIndex(array $acceptedRuns, array $run): ?int
	{
		// lower bound by [start, removedCount] - insertions come before replacements starting at the same line
		$low = 0;
		$high = count($acceptedRuns);
		while ($low < $high) {
			$mid = intdiv($low + $high, 2);
			if ([$acceptedRuns[$mid][0], $acceptedRuns[$mid][1]] < [$run[0], $run[1]]) {
				$low = $mid + 1;
			} else {
				$high = $mid;
			}
		}

		if (isset($acceptedRuns[$low]) && $acceptedRuns[$low] === $run) {
			return $low;
		}
		if ($low > 0 && !self::isBefore($acceptedRuns[$low - 1], $run)) {
			return null;
		}
		if (isset($acceptedRuns[$low]) && !self::isBefore($run, $acceptedRuns[$low])) {
			return null;
		}

		return $low;
	}

	/**
	 * @param array{int, int, list<string>} $a
	 * @param array{int, int, list<string>} $b
	 */
	private static function isBefore(array $a, array $b): bool
	{
		if ($a[1] === 0 && $b[1] === 0 && $a[0] === $b[0]) {
			// two different insertions at the same line - their order is ambiguous
			return false;
		}

		return $a[0] + $a[1] <= $b[0];
	}

	/**
	 * @return list<array{int, int, list<string>}> [0-based start line in the original file, number of removed lines, added lines]
	 */
	private static function parseChangeRuns(string $unifiedDiff): array
	{
		$runs = [];
		$run = null;
		$originalLine = 0;
		foreach (self::splitStringByLines($unifiedDiff) as $line) {
			if (str_starts_with($line, '@@')) {
				if ($run !== null) {
					$runs[] = $run;
					$run = null;
				}
				$matches = Strings::match($line, '/^@@ -(\d+)/');
				if ($matches !== null) {
					$originalLine = (int) $matches[1] - 1;
				}
				continue;
			}

			$prefix = $line[0];
			if ($prefix === '-') {
				$run ??= [$originalLine, 0, []];
				$run[1]++;
				$originalLine++;
			} elseif ($prefix === '+') {
				$run ??= [$originalLine, 0, []];
				$run[2][] = substr($line, 1);
			} elseif ($prefix === ' ') {
				if ($run !== null) {
					$runs[] = $run;
					$run = null;
				}
				$originalLine++;
			}
		}

		if ($run !== null) {
			$runs[] = $run;
		}

		return $runs;
	}

	/**
	 * @return string[]
	 */
	private static function splitStringByLines(string $input): array
	{
		return Strings::split($input, '/(.*\R)/', PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	}

}
