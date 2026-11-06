<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPUnit\Framework\TestCase;

class BenchmarkReportTest extends TestCase
{

	public function testRenderAnnotations(): void
	{
		$results = BenchmarkComparator::compare(
			[
				'a-ok.php' => ['instructions' => 1_000_000_000, 'peakMemory' => 100 * 1024 * 1024],
				'b-mixed.php' => ['instructions' => 2_000_000_000, 'peakMemory' => 100 * 1024 * 1024],
				'c-faster.php' => ['instructions' => 2_000_000_000],
				'e-small.php' => ['instructions' => 10_000_000],
			],
			[
				'a-ok.php' => ['instructions' => 1_010_000_000, 'peakMemory' => 100 * 1024 * 1024],
				// slower in instructions, lower in memory: listed once, as slower, with both metrics
				'b-mixed.php' => ['instructions' => 3_000_000_000, 'peakMemory' => 80 * 1024 * 1024],
				'c-faster.php' => ['instructions' => 1_000_000_000],
				'd-new.php' => ['instructions' => 1],
				// 30 % more, but within the 50 ms allowed below 100 ms
				'e-small.php' => ['instructions' => 13_000_000],
			],
		);

		$this->assertSame(
			"::error file=tests/bench/data/b-mixed.php,line=1,title=b-mixed.php is slower than expected::instructions +50.0 %25 (3.00 G, expected 2.00 G, allowed ±25 %25)%0ApeakMemory -20.0 %25 (80.0 MB, expected 100.0 MB, allowed ±10 %25)\n"
			. "::warning file=tests/bench/data/d-new.php,line=1,title=d-new.php has no expected numbers::expected.neon has no numbers for these yet. This does not fail the build, but accept the new numbers.\n"
			. "::warning file=tests/bench/data/c-faster.php,line=1,title=c-faster.php is faster than expected::instructions -50.0 %25 (1.00 G, expected 2.00 G, allowed ±25 %25)\n",
			BenchmarkReport::renderAnnotations($results),
		);
	}

	public function testRenderTerminalWhenAllMatch(): void
	{
		$results = BenchmarkComparator::compare(
			['a.php' => ['instructions' => 1_000_000_000], 'b.php' => ['instructions' => 1_000_000_000]],
			['a.php' => ['instructions' => 1_010_000_000], 'b.php' => ['instructions' => 990_000_000]],
		);

		$this->assertSame(
			"Benchmarks passed: all 2 match tests/bench/expected.neon\n"
			. "Allowed difference from tests/bench/expected.neon: instructions and estimatedCycles by the time they take at 5.8 M instructions or 7.4 M cycles per ms: ±50 ms below 100 ms, ±25 % from 100 ms, ±20 % from 500 ms, ±10 % from 2000 ms, each at least what the range below allows; ±10 % peakMemory.\n",
			BenchmarkReport::renderTerminal($results, 'accept', false),
		);
	}

}
