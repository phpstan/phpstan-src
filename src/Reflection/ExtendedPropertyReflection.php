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
 * @api-do-not-implement
 */
interface ExtendedPropertyReflection extends PropertyReflection
{

	public const HOOK_GET = 'get';

	public const HOOK_SET = 'set';

	public function getName(): string;

	public function hasPhpDocType(): bool;

	public function getPhpDocType(): Type;

	public function hasNativeType(): bool;

	public function getNativeType(): Type;

	public function isAbstract(): TrinaryLogic;

	public function isFinalByKeyword(): TrinaryLogic;

	public function isFinal(): TrinaryLogic;

	/**
	 * Virtual properties (PHP 8.4+) exist only through their get/set hooks
	 * and don't occupy memory in the object.
	 */
	public function isVirtual(): TrinaryLogic;

	/** @param self::HOOK_* $hookType */
	public function hasHook(string $hookType): bool;

	/**
	 * Property hooks (PHP 8.4+) are internally represented as methods.
	 *
	 * @param self::HOOK_* $hookType
	 */
	public function getHook(string $hookType): ExtendedMethodReflection;

	public function isProtectedSet(): bool;

	public function isPrivateSet(): bool;

	/** @return list<AttributeReflection> */
	public function getAttributes(): array;

	/**
	 * Returns yes() for properties that represent possibly-defined properties
	 * on non-final classes, mixed, object, etc. — placeholders PHPStan creates
	 * when it cannot prove a property doesn't exist.
	 */
	public function isDummy(): TrinaryLogic;

}
