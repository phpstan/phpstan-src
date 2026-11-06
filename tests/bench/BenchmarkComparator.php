<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function abs;
use function array_keys;
use function array_unique;
use function in_array;
use function max;
use function sort;
use const SORT_STRING;

/**
 * Compares measured benchmark metrics against the expected ones from tests/bench/expected.neon.
 * compare.php reads and writes the files; this class only works with the numbers.
 *
 * The metrics come from CodSpeed's CPU simulation, which gives the same counts on every run, so
 * the tolerances do not absorb noise between runs. They absorb small changes in code every
 * analysis goes through and what moves the counts without a code change, such as a new PHP patch
 * release on the runners. A benchmark is allowed to differ by a relative tolerance, but at least
 * by a fixed amount, so the quickest benchmarks, which mostly run that shared code, are not held
 * to a fraction of a millisecond.
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

	/** Allowed relative difference from the expected value, per metric */
	public const TOLERANCES = [
		'instructions' => 0.05,
		'estimatedCycles' => 0.05,
		'peakMemory' => 0.10,
	];

	/** Allowed difference from the expected value however small the benchmark is, per metric */
	public const MINIMUM_ALLOWED_DIFFERENCES = [
		'instructions' => 25_000_000,
		'estimatedCycles' => 35_000_000,
		'peakMemory' => 0,
	];

	/**
	 * @param array<string, array<string, int>> $expected metric values keyed by benchmark file name and metric name
	 * @param array<string, array<string, int>> $actual the same shape, as measured
	 * @return list<array{benchmark: string, metric: key-of<self::TOLERANCES>, expected: int|null, actual: int|null, change: float|null, allowedChange: float|null, status: self::STATUS_*}> sorted by benchmark, then in the order of TOLERANCES
	 */
	public static function compare(array $expected, array $actual): array
	{
		$benchmarks = array_unique([...array_keys($expected), ...array_keys($actual)]);
		sort($benchmarks, SORT_STRING);

		$results = [];
		foreach ($benchmarks as $benchmark) {
			foreach (self::TOLERANCES as $metric => $tolerance) {
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
					$allowedChange = max($tolerance, self::MINIMUM_ALLOWED_DIFFERENCES[$metric] / (float) $expectedValue);
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
