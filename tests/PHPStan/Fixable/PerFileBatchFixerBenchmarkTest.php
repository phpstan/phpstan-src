<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function file_put_contents;
use function fwrite;
use function implode;
use function microtime;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use const STDERR;

/**
 * @extends RuleTestCase<RenameVariableFixRule>
 */
final class PerFileBatchFixerBenchmarkTest extends RuleTestCase
{

	#[Override]
	protected function getRule(): Rule
	{
		return new RenameVariableFixRule();
	}

	public function testFiveHunksOverHundredLines(): void
	{
		$this->runBatchedBenchmark(100, 5);
	}

	public function testTwentyHunksOverFiveHundredLines(): void
	{
		$this->runBatchedBenchmark(500, 20);
	}

	public function testFiftyHunksOverThousandLines(): void
	{
		$this->runBatchedBenchmark(1000, 50);
	}

	public function testHundredHunksOverThousandLines(): void
	{
		$this->runBatchedBenchmark(1000, 100);
	}

	private function runBatchedBenchmark(int $fileLines, int $hunkCount): void
	{
		[$file, $expectedFile] = $this->writeSyntheticInput($fileLines, $hunkCount);

		try {
			$startedAt = microtime(true);
			$this->fix($file, $expectedFile);
			$elapsed = microtime(true) - $startedAt;
		} finally {
			@unlink($file);
			@unlink($expectedFile);
		}

		fwrite(
			STDERR,
			sprintf(
				"\n[BatchFixer bench] hunks=%d lines=%d t=%.3fs\n",
				$hunkCount,
				$fileLines,
				$elapsed,
			),
		);
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	private function writeSyntheticInput(int $fileLines, int $hunkCount): array
	{
		$lines = ["<?php\n", "function () {\n"];
		$expectedLines = ["<?php\n", "function () {\n"];
		for ($i = 0; $i < $fileLines; $i++) {
			if ($i < $hunkCount) {
				$varName = sprintf('a%d', $i);
				$lines[] = sprintf("\techo \$%s;\n", $varName);
				$expectedLines[] = sprintf("\techo \$%s%s;\n", $varName, $varName[-1]);
			} else {
				$lines[] = sprintf("\t// noise %d\n", $i);
				$expectedLines[] = sprintf("\t// noise %d\n", $i);
			}
		}
		$lines[] = "};\n";
		$expectedLines[] = "};\n";

		$inputPath = tempnam(sys_get_temp_dir(), 'phpstan-batch-bench-in-') . '.php';
		$expectedPath = tempnam(sys_get_temp_dir(), 'phpstan-batch-bench-exp-') . '.php';
		file_put_contents($inputPath, implode('', $lines));
		file_put_contents($expectedPath, implode('', $expectedLines));

		return [$inputPath, $expectedPath];
	}

}
