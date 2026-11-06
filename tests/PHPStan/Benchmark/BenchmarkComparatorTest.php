<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function round;

class BenchmarkComparatorTest extends TestCase
{

	public function testCompare(): void
	{
		// 1 s, where 20 % is allowed
		$instructions = self::countFor('instructions', 1000);
		$cycles = self::countFor('estimatedCycles', 1000);
		$expected = [
			'slow.php' => ['instructions' => $instructions, 'estimatedCycles' => $cycles, 'peakMemory' => 100_000_000],
			'fast.php' => ['instructions' => $instructions, 'estimatedCycles' => $cycles, 'peakMemory' => 100_000_000],
			'removed.php' => ['instructions' => $instructions],
		];
		$actual = [
			'slow.php' => [
				// exactly at the tolerance
				'instructions' => $instructions + self::countFor('instructions', 200),
				// just above it
				'estimatedCycles' => $cycles + self::countFor('estimatedCycles', 200) + 1,
				// within the peak memory one
				'peakMemory' => 108_000_000,
			],
			'fast.php' => [
				'instructions' => $instructions - self::countFor('instructions', 100),
				'estimatedCycles' => $cycles - self::countFor('estimatedCycles', 201),
				'peakMemory' => 89_000_000,
			],
			'added.php' => ['instructions' => 700],
		];

		$statuses = [];
		foreach (BenchmarkComparator::compare($expected, $actual) as $result) {
			$statuses[] = [$result['benchmark'], $result['metric'], $result['status']];
		}
		$this->assertSame([
			['added.php', 'instructions', 'not expected'],
			['fast.php', 'instructions', 'ok'],
			['fast.php', 'estimatedCycles', 'faster'],
			['fast.php', 'peakMemory', 'faster'],
			['removed.php', 'instructions', 'not measured'],
			['slow.php', 'instructions', 'ok'],
			['slow.php', 'estimatedCycles', 'slower'],
			['slow.php', 'peakMemory', 'ok'],
		], $statuses);
	}

	/**
	 * @return iterable<string, array{int, float}>
	 */
	public static function dataAllowedChange(): iterable
	{
		yield 'quickest range, allowed 50 % of its 100 ms' => [10, 50 / 10];
		yield 'just below 100 ms' => [99, 50 / 99];
		yield '25 % would be less than the 50 ms allowed below 100 ms' => [150, 50 / 150];
		yield '25 %' => [300, 0.25];
		yield '20 % would be less than the 125 ms allowed at 500 ms' => [600, 125 / 600];
		yield '20 %' => [1000, 0.2];
		yield '10 % would be less than the 400 ms allowed at 2 s' => [2500, 400 / 2500];
		yield '10 %' => [5000, 0.1];
	}

	#[DataProvider('dataAllowedChange')]
	public function testAllowedChange(int $milliseconds, float $allowedChange): void
	{
		foreach (['instructions', 'estimatedCycles'] as $metric) {
			$expected = self::countFor($metric, $milliseconds);
			$this->assertEqualsWithDelta($allowedChange, BenchmarkComparator::allowedChange($metric, $expected), 1e-9, $metric);

			$allowed = (int) round($expected * $allowedChange);
			$results = BenchmarkComparator::compare(['a.php' => [$metric => $expected]], ['a.php' => [$metric => $expected + $allowed]]);
			$this->assertSame('ok', $results[0]['status'], $metric);
			$results = BenchmarkComparator::compare(['a.php' => [$metric => $expected]], ['a.php' => [$metric => $expected + $allowed + 2]]);
			$this->assertSame('slower', $results[0]['status'], $metric);
		}

		$this->assertSame(0.1, BenchmarkComparator::allowedChange('peakMemory', 1_000_000));
	}

	public function testFails(): void
	{
		$instructions = self::countFor('instructions', 1000);
		$expected = ['a.php' => ['instructions' => $instructions, 'peakMemory' => 100_000_000]];

		$this->assertFalse(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => (int) ($instructions * 1.2), 'peakMemory' => 110_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => (int) ($instructions * 1.21), 'peakMemory' => 100_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, ['a.php' => ['instructions' => $instructions, 'peakMemory' => 111_000_000]])));
		$this->assertTrue(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, [])));
		// faster and new benchmarks only ask for expected.neon to be updated
		$this->assertFalse(BenchmarkComparator::fails(BenchmarkComparator::compare($expected, [
			'a.php' => ['instructions' => (int) ($instructions / 2), 'peakMemory' => 50_000_000],
			'b.php' => ['instructions' => 100],
		])));
	}

	/**
	 * @param 'instructions'|'estimatedCycles' $metric
	 */
	private static function countFor(string $metric, int $milliseconds): int
	{
		return $milliseconds * BenchmarkComparator::PER_MILLISECOND[$metric];
	}

}
