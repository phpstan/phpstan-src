<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/** @api */
class ConstantIntegerType extends IntegerType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;
	use ConstantScalarToBooleanTrait;
	use ConstantNumericComparisonTypeTrait;

	private int $value;

	/** @api */
	public function __construct(int $value)
	{
		parent::__construct();
		$this->value = $value;
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
			static function (): string {
				return 'int';
			},
			function (): string {
				return sprintf('%s', $this->value);
			}
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

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['value']);
	}

}
