<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

/** @api */
class IntegerType implements Type
{

	use JustNullableTypeTrait;
	use NonArrayTypeTrait;
	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonObjectTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGenericTypeTrait;
	use NonOffsetAccessibleTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct()
	{
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'int';
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

	public function toNumber(): Type
	{
		return $this;
	}

	public function toFloat(): Type
	{
		return new FloatType();
	}

	public function toInteger(): Type
	{
		return $this;
	}

	public function toString(): Type
	{
		return new IntersectionType([
			new StringType(),
			new AccessoryNumericStringType(),
		]);
	}

	public function toArray(): Type
	{
		return new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[$this],
			[1],
			[],
			true,
		);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof IntegerRangeType || $typeToRemove instanceof ConstantIntegerType) {
			if ($typeToRemove instanceof IntegerRangeType) {
				$removeValueMin = $typeToRemove->getMin();
				$removeValueMax = $typeToRemove->getMax();
			} else {
				$removeValueMin = $typeToRemove->getValue();
				$removeValueMax = $typeToRemove->getValue();
			}
			$lowerPart = $removeValueMin !== null ? IntegerRangeType::fromInterval(null, $removeValueMin, -1) : null;
			$upperPart = $removeValueMax !== null ? IntegerRangeType::fromInterval($removeValueMax, null, +1) : null;
			if ($lowerPart !== null && $upperPart !== null) {
				return new UnionType([$lowerPart, $upperPart]);
			}
			return $lowerPart ?? $upperPart ?? new NeverType();
		}

		return null;
	}

}
