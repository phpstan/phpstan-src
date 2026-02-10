<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Reflection for a constant (class constant or global constant).
 *
 * Provides the constant's name, resolved value type, deprecation status, and
 * metadata. This is the base interface — ClassConstantReflection extends it
 * with class-specific features (declaring class, value expression, native type).
 *
 * @api
 */
interface ConstantReflection
{

	/** Returns the constant name. */
	public function getName(): string;

	/** Returns the type of this constant's value. */
	public function getValueType(): Type;

	/** Whether this constant is marked as deprecated. */
	public function isDeprecated(): TrinaryLogic;

	/** Returns the deprecation message, or null if not deprecated. */
	public function getDeprecatedDescription(): ?string;

	/** Whether this constant is marked as @internal. */
	public function isInternal(): TrinaryLogic;

	/** Returns the file path where this constant is defined, or null for built-ins. */
	public function getFileName(): ?string;

	/**
	 * Returns PHP attributes on this constant.
	 *
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

}
