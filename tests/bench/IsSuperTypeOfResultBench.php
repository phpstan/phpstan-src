<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class IsSuperTypeOfResultBench
{

	/** @var list<IsSuperTypeOfResult> */
	private array $results = [];

	/** @var list<ConstantStringType> */
	private array $strings = [];

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

		IsSuperTypeOfResult::maxMin(...self::maybes(4));
		IsSuperTypeOfResult::extremeIdentity(...self::maybes(4));
		$this->string->isSuperTypeOf(new UnionType(self::strings('warmup', 4)));
		self::accessoriesIsSuperTypeOf(self::accessories(), self::strings('warmup', 4));

		$this->results = self::maybes($results);
		$this->strings = self::strings('string', $results);
		$this->union = new UnionType(self::strings('string', $results));
	}

	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '17.77K', cycles: '64.88K', memory: '1.102KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '234.9K', cycles: '392.2K', memory: '24.16KiB')]
	public function benchMaxMin(int $results): IsSuperTypeOfResult
	{
		return IsSuperTypeOfResult::maxMin(...$this->results);
	}

	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '19.58K', cycles: '75.07K', memory: '1.469KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '249.9K', cycles: '422.3K', memory: '32.22KiB')]
	public function benchExtremeIdentity(int $results): IsSuperTypeOfResult
	{
		return IsSuperTypeOfResult::extremeIdentity(...$this->results);
	}

	/**
	 * StringType::isSuperTypeOf() is asked about each constant string of the union.
	 */
	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '41.64K', cycles: '128.5K', memory: '1.469KiB')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '548.1K', cycles: '720.9K', memory: '32.22KiB')]
	public function benchStringIsSuperTypeOfUnion(int $results): IsSuperTypeOfResult
	{
		return $this->string->isSuperTypeOf($this->union);
	}

	/**
	 * Each accessory type is asked about each constant string.
	 */
	#[DataProvider(method: 'results')]
	#[ExpectedFor(dataSet: 'results-16', instructions: '262.0K', cycles: '350.0K', memory: '496B')]
	#[ExpectedFor(dataSet: 'results-256', instructions: '4.107M', cycles: '4.341M', memory: '496B')]
	public function benchAccessoriesIsSuperTypeOf(int $results): int
	{
		return self::accessoriesIsSuperTypeOf(self::accessories(), $this->strings);
	}

	/**
	 * @param list<AccessoryType> $accessories
	 * @param list<ConstantStringType> $strings
	 */
	private static function accessoriesIsSuperTypeOf(array $accessories, array $strings): int
	{
		$yes = 0;
		foreach ($accessories as $accessory) {
			foreach ($strings as $string) {
				if (!$accessory->isSuperTypeOf($string)->yes()) {
					continue;
				}

				$yes++;
			}
		}

		return $yes;
	}

	/**
	 * @return list<AccessoryType>
	 */
	private static function accessories(): array
	{
		return [
			new AccessoryNonEmptyStringType(),
			new AccessoryNonFalsyStringType(),
			new AccessoryLowercaseStringType(),
			new AccessoryUppercaseStringType(),
			new AccessoryNumericStringType(),
			new AccessoryLiteralStringType(),
		];
	}

	/**
	 * @return list<IsSuperTypeOfResult>
	 */
	private static function maybes(int $count): array
	{
		$results = [];
		for ($i = 0; $i < $count; $i++) {
			$results[] = new IsSuperTypeOfResult(TrinaryLogic::createMaybe(), []);
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
