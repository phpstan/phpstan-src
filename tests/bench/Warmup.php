<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function count;
use function in_array;
use function sprintf;
use function strlen;

/**
 * measure.php analyses this file before a benchmark, so the measured analysis does not
 * include loading and compiling the code paths every analysis goes through.
 * Not used at runtime.
 */
final class Warmup
{

	/** @var array<string, list<int>> */
	private array $values = [];

	public function __construct(private readonly string $name)
	{
	}

	/**
	 * @param list<int> $numbers
	 */
	public function add(string $key, array $numbers): void
	{
		foreach ($numbers as $number) {
			if ($number < 0 || in_array($number, $this->values[$key] ?? [], true)) {
				continue;
			}

			$this->values[$key][] = $number;
		}
	}

	/**
	 * @return array<string, int>
	 */
	public function totals(): array
	{
		$totals = [];
		foreach ($this->values as $key => $numbers) {
			$total = 0;
			foreach (array_filter($numbers, static fn (int $number): bool => $number % 2 === 0) as $number) {
				$total += $number;
			}
			$totals[$key] = $total;
		}

		return $totals;
	}

	public function describe(?int $limit = null): string
	{
		$lines = array_map(
			static fn (string $key, int $total): string => sprintf('%s: %d', $key, $total),
			array_keys($this->totals()),
			$this->totals(),
		);
		if ($limit !== null && count($lines) > $limit) {
			$lines = array_slice($lines, 0, $limit);
		}

		$description = match (count($lines)) {
			0 => 'empty',
			1 => 'one value',
			default => 'more values',
		};

		return strlen($this->name) > 0 ? $this->name . ' ' . $description : $description;
	}

}
