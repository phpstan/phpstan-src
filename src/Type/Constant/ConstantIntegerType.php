<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function is_float;
use function is_int;
use function is_numeric;
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

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->value === $type->value ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
		}

		if ($type instanceof IntegerRangeType) {
			$min = $type->getMin();
			$max = $type->getMax();
			if (($min === null || $min <= $this->value) && ($max === null || $this->value <= $max)) {
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createNo();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
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

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new IntegerType();
	}

	public function exponentiate(Type $exponent): Type
	{
		if ($exponent instanceof UnionType) {
			$results = [];
			foreach ($exponent->getTypes() as $unionType) {
				$results[] = $this->exponentiate($unionType);
			}
			return TypeCombinator::union(...$results);
		}

		if ($exponent instanceof IntegerRangeType) {
			$min = null;
			$max = null;
			if ($exponent->getMin() !== null) {
				$min = $this->getValue() ** $exponent->getMin();
			}
			if ($exponent->getMax() !== null) {
				$max = $this->getValue() ** $exponent->getMax();
			}

			if (!is_float($min) && !is_float($max)) {
				return IntegerRangeType::fromInterval($min, $max);
			}
		} elseif ($exponent instanceof ConstantScalarType) {
			$exponentValue = $exponent->getValue();
			if (is_int($exponentValue)) {
				$min = $this->getValue() ** $exponentValue;
				$max = $this->getValue() ** $exponentValue;

				if (!is_float($min) && !is_float($max)) {
					return IntegerRangeType::fromInterval($min, $max);
				}
			}

			if (is_numeric($exponentValue)) {
				return new ConstantFloatType($this->getValue() ** $exponentValue);
			}
		}

		if ($exponent instanceof ConstantScalarType) {
			$result = $this->getValue() ** $exponent->getValue();
			if (is_int($result)) {
				return new ConstantIntegerType($result);
			}
			return new ConstantFloatType($result);
		}

		return parent::exponentiate($exponent);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['value']);
	}

}
