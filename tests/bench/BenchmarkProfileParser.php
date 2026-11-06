<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function array_combine;
use function array_map;
use function count;
use function explode;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Turns the profile of one benchmark run into the metrics tests/bench/expected.neon holds.
 * run.php reads the files and runs the commands; this class only parses their contents.
 */
final class BenchmarkProfileParser
{

	/**
	 * Reads a callgrind output file written under CodSpeed's CPU simulation. measure.php names the
	 * part it measured after the benchmark, the boot and the warm-up before it are not counted.
	 *
	 * Estimated cycles use Valgrind's cycle estimation formula: instructions, plus 10 for each
	 * first-level cache miss, plus 100 for each last-level cache miss. CodSpeed simulates the same
	 * fixed cache sizes on every machine, so the misses do not depend on the runner's CPU.
	 *
	 * @param string $benchmark the name measure.php gave the measured part, like tests/bench/data/bug-10147.php
	 * @return array{instructions: int, estimatedCycles: int}|null null when the file has no part of that name
	 */
	public static function parseCallgrindOutput(string $contents, string $benchmark): ?array
	{
		$inBenchmarkPart = false;
		$events = [];
		foreach (explode("\n", $contents) as $line) {
			if (str_starts_with($line, 'part:')) {
				$inBenchmarkPart = false;
			} elseif ($line === 'desc: Trigger: Client Request: ' . $benchmark) {
				$inBenchmarkPart = true;
			} elseif (str_starts_with($line, 'events:')) {
				$events = self::splitFields(substr($line, 7));
			} elseif ($inBenchmarkPart && str_starts_with($line, 'totals:')) {
				$values = array_map('intval', self::splitFields(substr($line, 7)));
				if (count($values) !== count($events)) {
					return null;
				}
				$totals = array_combine($events, $values);
				$instructions = $totals['Ir'] ?? 0;
				$firstLevelMisses = ($totals['I1mr'] ?? 0) + ($totals['D1mr'] ?? 0) + ($totals['D1mw'] ?? 0);
				$lastLevelMisses = ($totals['ILmr'] ?? 0) + ($totals['DLmr'] ?? 0) + ($totals['DLmw'] ?? 0);

				return [
					'instructions' => $instructions,
					'estimatedCycles' => $instructions + 10 * $firstLevelMisses + 100 * $lastLevelMisses,
				];
			}
		}

		return null;
	}

	/**
	 * @return list<string>
	 */
	private static function splitFields(string $fields): array
	{
		return explode(' ', trim($fields));
	}

}
