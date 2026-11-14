<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class AcceptsResultBench
{

	/** @var list<AcceptsResult> */
	private array $results = [];

	private StringType $string;

	private UnionType $union;

	public function __construct(private ScratchDirectory $scratch)
	{
		$this->string = new StringType();
		$this->union = new UnionType([new ConstantStringType('a'), new ConstantStringType('b')]);
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function results(): iterable
	{
		foreach ([16, 256] as $results) {
			yield 'results-' . $results => [$results];
		}
	}

	#[Before]
	public function boot(int $results): void
	{
		BenchContainer::create($this->scratch);

		AcceptsResult::maxMin(...self::maybes(4));
		AcceptsResult::extremeIdentity(...self::maybes(4));
		$this->string->accepts(new UnionType(self::strings('warmup', 4)), true);

		$this->results = self::maybes($results);
		$this->union = new UnionType(self::strings('string', $results));
	}

	#[DataProvider(method: 'results')]
	public function benchMaxMin(int $results): AcceptsResult
	{
		return AcceptsResult::maxMin(...$this->results);
	}

	#[DataProvider(method: 'results')]
	public function benchExtremeIdentity(int $results): AcceptsResult
	{
		return AcceptsResult::extremeIdentity(...$this->results);
	}

	/**
	 * StringType::accepts() accepts each constant string of the union.
	 */
	#[DataProvider(method: 'results')]
	public function benchStringAcceptsUnion(int $results): AcceptsResult
	{
		return $this->string->accepts($this->union, true);
	}

	/**
	 * @return list<AcceptsResult>
	 */
	private static function maybes(int $count): array
	{
		$results = [];
		for ($i = 0; $i < $count; $i++) {
			$results[] = new AcceptsResult(TrinaryLogic::createMaybe(), []);
		}

		return $results;
	}

	/**
	 * @return list<ConstantStringType>
	 */
	private static function strings(string $prefix, int $count): array
	{
		$strings = [];
		for ($i = 0; $i < $count; $i++) {
			$strings[] = new ConstantStringType($prefix . $i);
		}

		return $strings;
	}

}
