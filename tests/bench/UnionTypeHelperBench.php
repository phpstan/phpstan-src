<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Type\Type;
use PHPStan\Type\UnionTypeHelper;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linearithmic;
use Tally\ScratchDirectory;
use function array_reverse;

final class UnionTypeHelperBench
{

	/** @var list<Type> */
	private array $cases = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function cases(): iterable
	{
		foreach ([16, 64, 256] as $cases) {
			yield 'cases-' . $cases => [$cases];
		}
	}

	#[Before]
	public function boot(int $cases): void
	{
		BenchContainer::create($this->scratch);

		$warmup = BenchEnumFile::create($this->scratch, 'Warmup4', 4);
		UnionTypeHelper::sortTypes(array_reverse(BenchEnumFile::cases($warmup, 4)));

		$enum = BenchEnumFile::create($this->scratch, 'Cases' . $cases, $cases);
		$this->cases = array_reverse(BenchEnumFile::cases($enum, $cases));
	}

	/**
	 * A match over a large enum builds a union of its remaining cases for every arm.
	 *
	 * @return list<Type>
	 */
	#[DataProvider(method: 'cases')]
	#[Linearithmic(size: 'cases')]
	public function benchSortEnumCases(int $cases): array
	{
		return UnionTypeHelper::sortTypes($this->cases);
	}

}
