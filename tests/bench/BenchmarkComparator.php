<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function abs;
use function array_keys;
use function array_unique;
use function in_array;
use function max;
use function min;
use function sort;
use const INF;
use const SORT_STRING;

/**
 * Compares measured benchmark metrics against the expected ones from tests/bench/expected.neon.
 * compare.php reads and writes the files; this class only works with the numbers.
 *
 * The metrics come from CodSpeed's CPU simulation, which gives the same counts on every run, so
 * the tolerances do not absorb noise between runs. They absorb changes too small to notice in
 * wall clock time, such as a small change in code every analysis goes through, or a new PHP patch
 * release on the runners. So the counted metrics are allowed the margins phpbench asserted on the
 * wall clock time of the analysis before (RegressionBench, removed with phpbench): the expected
 * count is converted to milliseconds by how many of it the analysis runs per millisecond, and the
 * quicker the benchmark, the larger the share of it that may change. Within each range the margin
 * is at least what the range below allows at its upper end, so a benchmark just past a range
 * boundary is not held to less than one just below it, and the quickest benchmarks are allowed
 * the margin of the slowest one in their range.
 *
 * Only a slower benchmark or one that was not measured fails the comparison. A faster or a new
 * benchmark passes, but the report asks for expected.neon to be updated, because until it is,
 * a later slowdown back to the old numbers stays within the tolerance.
 */
final class BenchmarkComparator
{

	public const STATUS_OK = 'ok';
	public const STATUS_SLOWER = 'slower';
	public const STATUS_FASTER = 'faster';
	public const STATUS_NOT_MEASURED = 'not measured';
	public const STATUS_NOT_EXPECTED = 'not expected';

	/** Statuses that fail the comparison */
	public const FAILING_STATUSES = [self::STATUS_SLOWER, self::STATUS_NOT_MEASURED];

	/** The compared metrics, in the order of the results */
	public const METRICS = ['instructions', 'estimatedCycles', 'peakMemory'];

	/**
	 * How many of each counted metric the analysis of a benchmark runs in a millisecond: the median
	 * over the benchmarks of their count divided by the wall clock time of the same analysis run
	 * natively (PHP 8.5, Intel Xeon @ 2.60 GHz), rounded. Over the 93 benchmarks it was 3.2–7.9 M
	 * instructions per millisecond for the middle 80 %; the quickest ones run fewer, because more of
	 * their time goes to work that does not grow with the file.
	 */
	public const PER_MILLISECOND = [
		'instructions' => 5_800_000,
		'estimatedCycles' => 7_400_000,
	];

	/** Allowed relative difference of a counted metric, keyed by the expected milliseconds from which it applies */
	public const DURATION_TOLERANCES = [
		0 => 0.5,
		100 => 0.25,
		500 => 0.2,
		2000 => 0.1,
	];

	/** Allowed relative difference of the peak memory */
	public const PEAK_MEMORY_TOLERANCE = 0.1;

	/**
	 * @param array<string, array<string, int>> $expected metric values keyed by benchmark file name and metric name
	 * @param array<string, array<string, int>> $actual the same shape, as measured
	 * @return list<array{benchmark: string, metric: value-of<self::METRICS>, expected: int|null, actual: int|null, change: float|null, allowedChange: float|null, status: self::STATUS_*}> sorted by benchmark, then in the order of METRICS
	 */
	public static function compare(array $expected, array $actual): array
	{
		$benchmarks = array_unique([...array_keys($expected), ...array_keys($actual)]);
		sort($benchmarks, SORT_STRING);

		$results = [];
		foreach ($benchmarks as $benchmark) {
			foreach (self::METRICS as $metric) {
				$expectedValue = $expected[$benchmark][$metric] ?? null;
				$actualValue = $actual[$benchmark][$metric] ?? null;
				if ($expectedValue === null && $actualValue === null) {
					continue;
				}

				$change = null;
				$allowedChange = null;
				if ($expectedValue === null) {
					$status = self::STATUS_NOT_EXPECTED;
				} elseif ($actualValue === null) {
					$status = self::STATUS_NOT_MEASURED;
				} else {
					$change = ($actualValue - $expectedValue) / (float) $expectedValue;
					$allowedChange = self::allowedChange($metric, $expectedValue);
					if (abs($change) <= $allowedChange) {
						$status = self::STATUS_OK;
					} elseif ($change > 0) {
						$status = self::STATUS_SLOWER;
					} else {
						$status = self::STATUS_FASTER;
					}
				}

				$results[] = [
					'benchmark' => $benchmark,
					'metric' => $metric,
					'expected' => $expectedValue,
					'actual' => $actualValue,
					'change' => $change,
					'allowedChange' => $allowedChange,
					'status' => $status,
				];
			}
		}

		return $results;
	}

	/**
	 * @param value-of<self::METRICS> $metric
	 */
	public static function allowedChange(string $metric, int $expectedValue): float
	{
		if ($metric === 'peakMemory') {
			return self::PEAK_MEMORY_TOLERANCE;
		}

		$expectedMilliseconds = $expectedValue / self::PER_MILLISECOND[$metric];
		$starts = array_keys(self::DURATION_TOLERANCES);
		$allowedMilliseconds = 0.0;
		foreach ($starts as $i => $start) {
			if ($start > $expectedMilliseconds) {
				break;
			}
			$end = $starts[$i + 1] ?? INF;
			$tolerance = self::DURATION_TOLERANCES[$start];
			// the quickest range is allowed its margin at its upper end, the others at least that
			$allowedMilliseconds = max($allowedMilliseconds, $tolerance * ($i === 0 ? $end : min($expectedMilliseconds, $end)));
		}

		return $allowedMilliseconds / $expectedMilliseconds;
	}

	/**
	 * @param list<array{status: self::STATUS_*, ...}> $results from compare()
	 */
	public static function fails(array $results): bool
	{
		foreach ($results as $result) {
			if (in_array($result['status'], self::FAILING_STATUSES, true)) {
				return true;
			}
		}

		return false;
	}

}
