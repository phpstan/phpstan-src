<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * A type whose value is known at analysis time — a compile-time constant scalar.
 *
 * Implemented by ConstantIntegerType, ConstantFloatType, ConstantStringType,
 * ConstantBooleanType, and NullType. These types represent specific known values
 * rather than just their general category.
 *
 * For example, ConstantStringType('hello') represents the specific string 'hello',
 * while StringType represents any string.
 *
 * PHPStan tracks constant values to enable precise analysis of:
 * - Array shapes (constant string keys)
 * - Switch/match exhaustiveness
 * - String operations with known inputs
 * - Arithmetic with known values
 *
 * Use Type::isConstantValue() to check if a type is constant without instanceof,
 * and Type::getConstantScalarTypes() to extract constant types from unions.
 *
 * @api
 */
interface ConstantScalarType extends Type
{

	/**
	 * Returns the actual PHP value this type represents.
	 *
	 * @return int|float|string|bool|null
	 */
	public function getValue();

}
