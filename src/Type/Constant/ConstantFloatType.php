<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function strpos;

/** @api */
class ConstantFloatType extends FloatType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;
	use ConstantScalarToBooleanTrait;
	use ConstantNumericComparisonTypeTrait;

	/** @api */
	public function __construct(private float $value)
	{
		parent::__construct();
	}

	public function getValue(): float
	{
		return $this->value;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'float',
			function (): string {
				$formatted = (string) $this->value;
				if (strpos($formatted, '.') === false) {
					$formatted .= '.0';
				}

				return $formatted;
			},
		);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			if (!$this->equals($type)) {
				if ($this->describe(VerbosityLevel::value()) === $type->describe(VerbosityLevel::value())) {
					return TrinaryLogic::createMaybe();
				}

				return TrinaryLogic::createNo();
			}

			return TrinaryLogic::createYes();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

}
