<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
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
class BooleanType implements Type
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

	public function getConstantStrings(): array
	{
		return [];
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'bool';
	}

	public function toNumber(): Type
	{
		return $this->toInteger();
	}

	public function toAbsoluteNumber(): Type
	{
		return $this->toNumber()->toAbsoluteNumber();
	}

	public function toString(): Type
	{
		return TypeCombinator::union(
			new ConstantStringType(''),
			new ConstantStringType('1'),
		);
	}

	public function toInteger(): Type
	{
		return TypeCombinator::union(
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
		);
	}

	public function toFloat(): Type
	{
		return TypeCombinator::union(
			new ConstantFloatType(0.0),
			new ConstantFloatType(1.0),
		);
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
		return new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
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
		return TrinaryLogic::createMaybe();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof ConstantBooleanType) {
			return new ConstantBooleanType(!$typeToRemove->getValue());
		}

		return null;
	}

	public function getFiniteTypes(): array
	{
		return [
			new ConstantBooleanType(true),
			new ConstantBooleanType(false),
		];
	}

	public function exponentiate(Type $exponent): Type
	{
		return ExponentiateHelper::exponentiate($this, $exponent);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('bool');
	}

	public function toTrinaryLogic(): TrinaryLogic
	{
		if ($this->isTrue()->yes()) {
			return TrinaryLogic::createYes();
		}
		if ($this->isFalse()->yes()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

}
