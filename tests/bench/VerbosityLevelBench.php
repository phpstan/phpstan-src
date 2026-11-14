<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class VerbosityLevelBench
{

	private ?Type $shape = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function values(): iterable
	{
		foreach ([16, 256] as $values) {
			yield 'values-' . $values => [$values];
		}
	}

	#[Before]
	public function boot(int $values): void
	{
		BenchContainer::create($this->scratch);
		VerbosityLevel::getRecommendedLevelByType(self::shape(4));
		$this->shape = self::shape($values);
	}

	/**
	 * The first value of the array shape, a lowercase-string, already needs the precise level,
	 * so the arrays in the other values are not looked into.
	 */
	#[DataProvider(method: 'values')]
	#[ExpectedFor(dataSet: 'values-16', instructions: '89.62K', cycles: '224.3K', memory: '2.508KiB')]
	#[ExpectedFor(dataSet: 'values-256', instructions: '749.4K', cycles: '937.2K', memory: '9.930KiB')]
	public function benchGetRecommendedLevelByType(int $values): VerbosityLevel
	{
		if ($this->shape === null) {
			throw new LogicException('boot() did not run.');
		}

		return VerbosityLevel::getRecommendedLevelByType($this->shape);
	}

	/**
	 * array{lowercase-string, array{0, 1, 2, 3}, array{4, 5, 6, 7}, …}
	 */
	private static function shape(int $values): Type
	{
		$keyTypes = [new ConstantIntegerType(0)];
		$valueTypes = [new IntersectionType([new StringType(), new AccessoryLowercaseStringType()])];
		for ($i = 1; $i < $values; $i++) {
			$keyTypes[] = new ConstantIntegerType($i);
			$innerKeyTypes = [];
			$innerValueTypes = [];
			for ($j = 0; $j < 4; $j++) {
				$innerKeyTypes[] = new ConstantIntegerType($j);
				$innerValueTypes[] = new ConstantIntegerType($i * 4 + $j);
			}
			$valueTypes[] = new ConstantArrayType($innerKeyTypes, $innerValueTypes, [4]);
		}

		return new ConstantArrayType($keyTypes, $valueTypes, [$values]);
	}

}
