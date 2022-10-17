<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/** @api */
class ConstantBooleanType extends BooleanType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;

	/** @api */
	public function __construct(private bool $value)
	{
		parent::__construct();
	}

	public function getValue(): bool
	{
		return $this->value;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->value ? 'true' : 'false';
	}

	public function getSmallerType(): Type
	{
		if ($this->value) {
			return StaticTypeFactory::falsey();
		}
		return new NeverType();
	}

	public function getSmallerOrEqualType(): Type
	{
		if ($this->value) {
			return new MixedType();
		}
		return StaticTypeFactory::falsey();
	}

	public function getGreaterType(): Type
	{
		if ($this->value) {
			return new NeverType();
		}
		return StaticTypeFactory::truthy();
	}

	public function getGreaterOrEqualType(): Type
	{
		if ($this->value) {
			return StaticTypeFactory::truthy();
		}
		return new MixedType();
	}

	public function toBoolean(): BooleanType
	{
		return $this;
	}

	public function toNumber(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function toFloat(): Type
	{
		return new ConstantFloatType((float) $this->value);
	}

	public function toArrayKey(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new BooleanType();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['value']);
	}

}
