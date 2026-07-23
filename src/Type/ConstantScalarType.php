<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * A type whose value is known at analysis time — a compile-time constant scalar.
 *
 * Implemented by ConstantIntegerType, ConstantFloatType, ConstantStringType,
 * ConstantBooleanType, and NullType.
 *
 * Use Type::isConstantValue() to check if a type is constant without instanceof,
 * and Type::getConstantScalarTypes() to extract constant types from unions.
 *
 * @api
 */
#[InstanceofDeprecated(insteadUse: 'Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()')]
interface ConstantScalarType extends Type
{

	/** @return int|float|string|bool|null */
	public function getValue();

}
