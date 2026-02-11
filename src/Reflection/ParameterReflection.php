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

	public function getName(): string;

	public function isOptional(): bool;

	public function getType(): Type;

	public function passedByReference(): PassedByReference;

	public function isVariadic(): bool;

	public function getDefaultValue(): ?Type;

}
