<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/**
 * Reflection for a function/method parameter.
 *
 * Represents a single parameter in a function or method signature. Each parameter
 * has a name, type, and metadata about optionality, variadicity, and pass-by-reference.
 *
 * The type returned by getType() is the combined PHPDoc + native type.
 * For separate PHPDoc and native types, see ExtendedParameterReflection.
 *
 * Part of a ParametersAcceptor which describes a complete function signature.
 *
 * @api
 */
interface ParameterReflection
{

	/** Returns the parameter name (without the $ prefix). */
	public function getName(): string;

	/**
	 * Whether this parameter is optional (has a default value or is variadic).
	 */
	public function isOptional(): bool;

	/**
	 * Returns the parameter's type (combined PHPDoc + native type).
	 */
	public function getType(): Type;

	/**
	 * Returns how this parameter is passed: by value, by reference (reads existing),
	 * or by reference (creates new variable).
	 */
	public function passedByReference(): PassedByReference;

	/** Whether this parameter is variadic (...$param). */
	public function isVariadic(): bool;

	/**
	 * Returns the type of the default value, or null if the parameter has no default.
	 */
	public function getDefaultValue(): ?Type;

}
