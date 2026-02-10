<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Extended property reflection with additional metadata beyond PropertyReflection.
 *
 * This interface exists to allow PHPStan to add new property query methods in minor
 * versions without breaking existing PropertiesClassReflectionExtension implementations.
 * Extension developers should implement PropertyReflection, not this interface — PHPStan
 * wraps PropertyReflection implementations to provide ExtendedPropertyReflection.
 *
 * Provides access to:
 * - Separate PHPDoc type vs native type (for resolving the effective type)
 * - Property hooks (PHP 8.4+) — get/set hooks with their own method reflections
 * - Asymmetric visibility (PHP 8.4+) — different read/write visibility
 * - Abstract/final/virtual modifiers
 * - PHP attributes
 *
 * This is the return type of Type::getProperty(), Type::getInstanceProperty(),
 * and Type::getStaticProperty().
 *
 * @api
 */
interface ExtendedPropertyReflection extends PropertyReflection
{

	public const HOOK_GET = 'get';

	public const HOOK_SET = 'set';

	/** Returns the property name. */
	public function getName(): string;

	/** Whether this property has a PHPDoc @var type. */
	public function hasPhpDocType(): bool;

	/**
	 * Returns the PHPDoc @var type for this property.
	 *
	 * If no PHPDoc type exists, returns MixedType.
	 */
	public function getPhpDocType(): Type;

	/** Whether this property has a native PHP type declaration. */
	public function hasNativeType(): bool;

	/**
	 * Returns the native PHP type declaration for this property.
	 *
	 * If no native type exists, returns MixedType.
	 */
	public function getNativeType(): Type;

	/** Whether this property is abstract (requires implementation in child class). */
	public function isAbstract(): TrinaryLogic;

	/** Whether this property has the `final` keyword explicitly. */
	public function isFinalByKeyword(): TrinaryLogic;

	/** Whether this property is effectively final (by keyword or other means). */
	public function isFinal(): TrinaryLogic;

	/**
	 * Whether this is a virtual property (has hooks but no backing store).
	 *
	 * Virtual properties exist only through their get/set hooks and don't
	 * occupy memory in the object. Introduced in PHP 8.4.
	 */
	public function isVirtual(): TrinaryLogic;

	/**
	 * Whether this property has the given hook type ('get' or 'set').
	 *
	 * @param self::HOOK_* $hookType
	 */
	public function hasHook(string $hookType): bool;

	/**
	 * Returns the method reflection for the given hook type.
	 *
	 * Property hooks (PHP 8.4+) are internally represented as methods.
	 *
	 * @param self::HOOK_* $hookType
	 */
	public function getHook(string $hookType): ExtendedMethodReflection;

	/** Whether this property has protected(set) asymmetric visibility. */
	public function isProtectedSet(): bool;

	/** Whether this property has private(set) asymmetric visibility. */
	public function isPrivateSet(): bool;

	/**
	 * Returns PHP attributes on this property.
	 *
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

	/**
	 * Whether this is a "dummy" property that may not actually exist.
	 *
	 * Returns no() for properties declared in code.
	 * Returns yes() for properties that represent possibly-defined properties
	 * on non-final classes, mixed, object, etc. — these are placeholders
	 * PHPStan creates when it cannot prove a property doesn't exist.
	 */
	public function isDummy(): TrinaryLogic;

}
