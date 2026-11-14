<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

/**
 * Each case asks the same intersection a thousand times, the way the analysis asks the types of
 * one expression again and again.
 */
final class IntersectionTypeQueriesBench
{

	private const CALLS = 1000;

	private ?IntersectionType $array = null;

	private ?IntersectionType $string = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		// non-empty-list<mixed> with offset 0
		$this->array = new IntersectionType([
			new ArrayType(new IntegerType(), new MixedType()),
			new NonEmptyArrayType(),
			new AccessoryArrayListType(),
			new HasOffsetType(new ConstantIntegerType(0)),
		]);
		$this->string = new IntersectionType([
			new StringType(),
			new AccessoryNonEmptyStringType(),
			new AccessoryNumericStringType(),
			new AccessoryLowercaseStringType(),
		]);
		$this->array->isOffsetAccessible();
		$this->string->describe(VerbosityLevel::precise());
	}

	public function benchIsOffsetAccessible(): TrinaryLogic
	{
		if ($this->array === null) {
			throw new LogicException('boot() did not run.');
		}

		$result = TrinaryLogic::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->array->isOffsetAccessible();
		}

		return $result;
	}

	public function benchDescribe(): string
	{
		if ($this->string === null) {
			throw new LogicException('boot() did not run.');
		}

		$description = '';
		for ($i = 0; $i < self::CALLS; $i++) {
			$description = $this->string->describe(VerbosityLevel::precise());
		}

		return $description;
	}

}
