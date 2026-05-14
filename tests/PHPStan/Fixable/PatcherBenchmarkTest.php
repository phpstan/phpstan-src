<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\Testing\PHPStanTestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function file_put_contents;
use function fwrite;
use function hash;
use function implode;
use function microtime;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;
use const STDERR;

final class PatcherBenchmarkTest extends PHPStanTestCase
{

	/**
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	public function testFiveHunksOverHundredLines(): void
	{
		$this->runBenchmark(100, 5);
	}

	/**
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	public function testTwentyHunksOverFiveHundredLines(): void
	{
		$this->runBenchmark(500, 20);
	}

	/**
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	public function testFiftyHunksOverThousandLines(): void
	{
		$this->runBenchmark(1000, 50);
	}

	/**
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	public function testHundredHunksOverThousandLines(): void
	{
		$this->runBenchmark(1000, 100);
	}

	/**
	 * @throws FileChangedException
	 * @throws MergeConflictException
	 */
	private function runBenchmark(int $fileLines, int $hunkCount): void
	{
		[$originalContent, $expectedContent, $diffs] = $this->buildSyntheticInput($fileLines, $hunkCount);

		$patcher = self::getContainer()->getByType(Patcher::class);

		$startedAt = microtime(true);
		$result = $patcher->applyDiffs($this->writeTempFile($originalContent), $diffs);
		$elapsed = microtime(true) - $startedAt;

		fwrite(
			STDERR,
			sprintf(
				"\n[Patcher bench] hunks=%d lines=%d t=%.3fs\n",
				$hunkCount,
				$fileLines,
				$elapsed,
			),
		);

		self::assertSame(
			$this->normalizeLineEndings($expectedContent),
			$this->normalizeLineEndings($result),
		);
	}

	/**
	 * @return array{0: string, 1: string, 2: list<FixedErrorDiff>}
	 */
	private function buildSyntheticInput(int $fileLines, int $hunkCount): array
	{
		$originalLines = [];
		for ($i = 0; $i < $fileLines; $i++) {
			$originalLines[] = sprintf("echo %d;\n", $i);
		}
		$originalContent = "<?php\n" . implode('', $originalLines);
		$originalHash = hash('sha256', $originalContent);

		$step = (int) ($fileLines / $hunkCount);
		if ($step < 1) {
			$step = 1;
		}

		$differ = new Differ(new UnifiedDiffOutputBuilder('', true));

		$expectedLines = $originalLines;
		$diffs = [];
		for ($i = 0; $i < $hunkCount; $i++) {
			$lineIndex = $i * $step;
			$newLine = sprintf("echo (%d+1000);\n", $lineIndex);

			$modified = $originalLines;
			$modified[$lineIndex] = $newLine;
			$modifiedContent = "<?php\n" . implode('', $modified);

			$diffs[] = new FixedErrorDiff(
				$originalHash,
				$differ->diff($originalContent, $modifiedContent),
			);

			$expectedLines[$lineIndex] = $newLine;
		}

		$expectedContent = "<?php\n" . implode('', $expectedLines);

		return [$originalContent, $expectedContent, $diffs];
	}

	private function writeTempFile(string $content): string
	{
		$path = sys_get_temp_dir() . '/phpstan-patcher-bench-' . hash('sha256', $content) . '.php';
		file_put_contents($path, $content);
		return $path;
	}

	private function normalizeLineEndings(string $string): string
	{
		return str_replace("\r\n", "\n", $string);
	}

}
