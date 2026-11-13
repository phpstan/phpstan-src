<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Internal\CombinationsHelper;
use Tally\Attribute\Before;
use Tally\Attribute\Complexity;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Exponential;
use Tally\ScratchDirectory;
use function array_fill;
use function count;

final class CombinationsHelperBench
{

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{list<list<int>>}>
	 */
	public static function pairs(): iterable
	{
		foreach ([4, 6, 8, 10] as $lists) {
			yield 'lists-' . $lists => [array_fill(0, $lists, [1, 2])];
		}
	}

	/**
	 * @return iterable<string, array{list<list<int>>}>
	 */
	public static function triples(): iterable
	{
		foreach ([3, 4, 5, 6] as $lists) {
			yield 'lists-' . $lists => [array_fill(0, $lists, [1, 2, 3])];
		}
	}

	/**
	 * @param list<list<int>> $lists
	 */
	#[Before]
	public function boot(array $lists): void
	{
		BenchContainer::create($this->scratch);
		self::count([[1, 2], [3, 4]]);
	}

	/**
	 * @param list<list<int>> $lists
	 */
	#[DataProvider(method: 'pairs')]
	#[Exponential(size: 'lists')]
	public function benchPairs(array $lists): int
	{
		return self::count($lists);
	}

	/**
	 * @param list<list<int>> $lists
	 */
	#[DataProvider(method: 'triples')]
	#[Complexity(size: 'lists', formula: '3^n')]
	public function benchTriples(array $lists): int
	{
		return self::count($lists);
	}

	/**
	 * @param list<list<int>> $lists
	 */
	private static function count(array $lists): int
	{
		$combinations = 0;
		foreach (CombinationsHelper::combinations($lists) as $combination) {
			$combinations += count($combination);
		}

		return $combinations;
	}

}
