<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function abs;
use function sprintf;

/** @api */
class ConstantIntegerType extends IntegerType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;
	use ConstantScalarToBooleanTrait;
	use ConstantNumericComparisonTypeTrait;

	/** @api */
	public function __construct(private int $value)
	{
		parent::__construct();
	}

	public function getValue(): int
	{
		return $this->value;
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return $this->value === $type->value ? IsSuperTypeOfResult::createYes() : IsSuperTypeOfResult::createNo();
		}

		if ($type instanceof IntegerRangeType) {
			$min = $type->getMin();
			$max = $type->getMax();
			if (($min === null || $min <= $this->value) && ($max === null || $this->value <= $max)) {
				return IsSuperTypeOfResult::createMaybe();
			}

			return IsSuperTypeOfResult::createNo();
		}

		if ($type instanceof parent) {
			return IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'int',
			fn (): string => sprintf('%s', $this->value),
		);
	}

	public function toFloat(): Type
	{
		return new ConstantFloatType($this->value);
	}

	public function toAbsoluteNumber(): Type
	{
		return new self(abs($this->value));
	}

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		if (!$strictTypes) {
			return TypeCombinator::union($this, $this->toFloat(), $this->toString(), $this->toBoolean());
		}

		return TypeCombinator::union($this, $this->toFloat());
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new IntegerType();
	}

	/**
	 * @return ConstTypeNode
	 */
	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(new ConstExprIntegerNode((string) $this->value));
	}

}
