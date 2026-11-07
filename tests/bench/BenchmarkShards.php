<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function array_filter;
use function array_keys;
use function array_values;
use function arsort;
use function count;
use function ksort;
use function min;
use function range;
use const SORT_NUMERIC;
use const SORT_STRING;

/**
 * Splits the benchmarks into the shards of the Benchmarks workflow so that the shards finish
 * at about the same time. shard.php reads the costs from tests/bench/expected.neon.
 */
final class BenchmarkShards
{

	/**
	 * Hands out the benchmarks from the most expensive one, each to the shard with the lowest
	 * total cost so far (longest-processing-time-first scheduling).
	 *
	 * @param array<string, int> $costs expected cost keyed by benchmark
	 * @return list<list<string>> benchmarks of each shard from the most expensive one, without empty shards
	 */
	public static function split(array $costs, int $count): array
	{
		// ties are broken by name, so the shards stay the same while the costs do
		ksort($costs, SORT_STRING);
		arsort($costs, SORT_NUMERIC);

		$shards = [];
		$totals = [];
		foreach (range(0, $count - 1) as $shard) {
			$shards[$shard] = [];
			$totals[$shard] = 0;
		}

		foreach ($costs as $benchmark => $cost) {
			$cheapestShard = array_keys($totals, min($totals), true)[0];
			$shards[$cheapestShard][] = $benchmark;
			$totals[$cheapestShard] += $cost;
		}

		return array_values(array_filter($shards, static fn (array $benchmarks): bool => count($benchmarks) > 0));
	}

}
