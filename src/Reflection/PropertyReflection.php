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

	public function getReadableType(): Type;

	/**
	 * May differ from the readable type for properties with asymmetric visibility
	 * or property hooks with different get/set types.
	 */
	public function getWritableType(): Type;

	/**
	 * Returns true when the readable and writable types are the same and no property hooks
	 * transform the value — PHPStan can then narrow the property's type based on assignments.
	 * Returns false when read and write types differ (e.g. `@property` with asymmetric types,
	 * property hooks, virtual properties).
	 */
	public function canChangeTypeAfterAssignment(): bool;

	public function isReadable(): bool;

	public function isWritable(): bool;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isInternal(): TrinaryLogic;

}
