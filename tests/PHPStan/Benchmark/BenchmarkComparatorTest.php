<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPUnit\Framework\TestCase;

class BenchmarkComparatorTest extends TestCase
{

	public function testCompare(): void
	{
		$expected = [
			'slow.php' => ['instructions' => 1_000_000_000, 'estimatedCycles' => 2_000_000_000, 'peakMemory' => 100_000_000],
			'fast.php' => ['instructions' => 1_000_000_000, 'estimatedCycles' => 2_000_000_000, 'peakMemory' => 100_000_000],
			'removed.php' => ['instructions' => 500_000_000],
		];
		$actual = [
			'slow.php' => [
				// exactly at the 5 % tolerance
				'instructions' => 1_050_000_000,
				// just above it
				'estimatedCycles' => 2_102_000_000,
				// above the instructions' tolerance but within the memory one
				'peakMemory' => 108_000_000,
			],
			'fast.php' => ['instructions' => 951_000_000, 'estimatedCycles' => 1_898_000_000, 'peakMemory' => 89_000_000],
			'added.php' => ['instructions' => 700],
		];

		$this->assertSame([
			['benchmark' => 'added.php', 'metric' => 'instructions', 'expected' => null, 'actual' => 700, 'change' => null, 'allowedChange' => null, 'status' => 'not expected'],
			['benchmark' => 'fast.php', 'metric' => 'instructions', 'expected' => 1_000_000_000, 'actual' => 951_000_000, 'change' => -0.049, 'allowedChange' => 0.05, 'status' => 'ok'],
			['benchmark' => 'fast.php', 'metric' => 'estimatedCycles', 'expected' => 2_000_000_000, 'actual' => 1_898_000_000, 'change' => -0.051, 'allowedChange' => 0.05, 'status' => 'faster'],
			['benchmark' => 'fast.php', 'metric' => 'peakMemory', 'expected' => 100_000_000, 'actual' => 89_000_000, 'change' => -0.11, 'allowedChange' => 0.1, 'status' => 'faster'],
			['benchmark' => 'removed.php', 'metric' => 'instructions', 'expected' => 500_000_000, 'actual' => null, 'change' => null, 'allowedChange' => null, 'status' => 'not measured'],
			['benchmark' => 'slow.php', 'metric' => 'instructions', 'expected' => 1_000_000_000, 'actual' => 1_050_000_000, 'change' => 0.05, 'allowedChange' => 0.05, 'status' => 'ok'],
			['benchmark' => 'slow.php', 'metric' => 'estimatedCycles', 'expected' => 2_000_000_000, 'actual' => 2_102_000_000, 'change' => 0.051, 'allowedChange' => 0.05, 'status' => 'slower'],
			['benchmark' => 'slow.php', 'metric' => 'peakMemory', 'expected' => 100_000_000, 'actual' => 108_000_000, 'change' => 0.08, 'allowedChange' => 0.1, 'status' => 'ok'],
		], BenchmarkComparator::compare($expected, $actual));
	}

	public function testSmallBenchmarkIsAllowedTheMinimumDifference(): void
	{
		$minimum = BenchmarkComparator::MINIMUM_ALLOWED_DIFFERENCES['instructions'];
		// 5 % of it is far below the minimum, so the minimum applies
		$expected = ['small.php' => ['instructions' => $minimum * 2]];

		$results = BenchmarkComparator::compare($expected, ['small.php' => ['instructions' => $minimum * 3]]);
		$this->assertSame(0.5, $results[0]['allowedChange']);
		$this->assertSame('ok', $results[0]['status']);

		$results = BenchmarkComparator::compare($expected, ['small.php' => ['instructions' => $minimum * 3 + 1]]);
		$this->assertSame('slower', $results[0]['status']);

		// once 5 % is more than the minimum, it applies instead
		$large = $minimum * 100;
		$results = BenchmarkComparator::compare(['large.php' => ['instructions' => $large]], ['large.php' => ['instructions' => $large + $minimum * 2]]);
		$this->assertSame(0.05, $results[0]['allowedChange']);
		$this->assertSame('ok', $results[0]['status']);
		$results = BenchmarkComparator::compare(['large.php' => ['instructions' => $large]], ['large.php' => ['instructions' => $large + $minimum * 6]]);
		$this->assertSame('slower', $results[0]['status']);
	}

	public function testFails(): void
	{
		$expected = ['a.php' => ['instructions' => 1_000_000_000, 'peakMemory' => 100_000_000]];

		$this->assertFalse(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => 1_050_000_000, 'peakMemory' => 110_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => 1_060_000_000, 'peakMemory' => 100_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => 1_000_000_000, 'peakMemory' => 111_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, [])));
		// faster and new benchmarks only ask for expected.neon to be updated
		$this->assertFalse(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, [
			'a.php' => ['instructions' => 500_000_000, 'peakMemory' => 50_000_000],
			'b.php' => ['instructions' => 100],
		])));
	}

}
