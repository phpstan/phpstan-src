<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Reflection for a class property.
 *
 * This is the interface extension developers should implement when creating
 * custom PropertiesClassReflectionExtension implementations for magic properties.
 *
 * Properties have separate readable and writable types to support:
 * - Asymmetric types (PHP 8.4+ property hooks with different get/set types)
 * - Read-only properties (readable but not writable)
 * - Write-only properties (writable but not readable, rare)
 *
 * For additional property metadata (native types, PHPDoc types, hooks, attributes),
 * see ExtendedPropertyReflection which extends this interface.
 *
 * @api
 */
interface PropertyReflection extends ClassMemberReflection
{

	/**
	 * Returns the type seen when reading from this property.
	 *
	 * This is the combined PHPDoc + native type that PHPStan uses for analysis.
	 */
	public function getReadableType(): Type;

	/**
	 * Returns the type accepted when writing to this property.
	 *
	 * May differ from the readable type for properties with asymmetric visibility
	 * or property hooks with different get/set types.
	 */
	public function getWritableType(): Type;

	/**
	 * Whether the property's type can change after assignment.
	 *
	 * Returns false for typed properties (which always retain their declared type)
	 * and true for untyped properties (which take on the type of whatever is assigned).
	 */
	public function canChangeTypeAfterAssignment(): bool;

	/** Whether this property can be read from. */
	public function isReadable(): bool;

	/** Whether this property can be written to. */
	public function isWritable(): bool;

	/** Whether this property is marked as deprecated. */
	public function isDeprecated(): TrinaryLogic;

	/** Returns the deprecation message, or null if not deprecated. */
	public function getDeprecatedDescription(): ?string;

	/** Whether this property is marked as @internal. */
	public function isInternal(): TrinaryLogic;

}
