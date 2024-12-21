<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
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

	public function getConstantStrings(): array
	{
		return [];
	}

	public function toNumber(): Type
	{
		return $this;
	}

	public function toAbsoluteNumber(): Type
	{
		return IntegerRangeType::createAllGreaterThanOrEqualTo(0);
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
			new AccessoryLowercaseStringType(),
			new AccessoryUppercaseStringType(),
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
			TrinaryLogic::createYes(),
		);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
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

	public function isFloat(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isArray()->yes()) {
			return new ConstantBooleanType(false);
		}

		if (
			$phpVersion->nonNumericStringAndIntegerIsFalseOnLooseComparison()
			&& $type->isString()->yes()
			&& $type->isNumericString()->no()
		) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
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

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function exponentiate(Type $exponent): Type
	{
		return ExponentiateHelper::exponentiate($this, $exponent);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('int');
	}

}
