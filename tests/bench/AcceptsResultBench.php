<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
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
	#[ExpectedFor(dataSet: 'results-16', instructions: '16.91K', cycles: '70.34K', memory: '1.180KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '212.4K', cycles: '362.0K', memory: '24.24KiB')]
	public function benchMaxMin(int $results): AcceptsResult
	{
		return AcceptsResult::maxMin(...$this->results);
	}

	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '18.73K', cycles: '80.59K', memory: '1.547KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '227.3K', cycles: '392.4K', memory: '32.30KiB')]
	public function benchExtremeIdentity(int $results): AcceptsResult
	{
		return AcceptsResult::extremeIdentity(...$this->results);
	}

	/**
	 * StringType::accepts() accepts each constant string of the union.
	 */
	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '43.10K', cycles: '136.6K', memory: '1.547KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '557.0K', cycles: '736.1K', memory: '32.30KiB')]
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
