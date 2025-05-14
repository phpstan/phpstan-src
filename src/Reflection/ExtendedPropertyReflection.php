<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * The purpose of this interface is to be able to
 * answer more questions about properties
 * without breaking backward compatibility
 * with existing PropertiesClassReflectionExtension.
 *
 * Developers are meant to only implement PropertyReflection
 * and its methods in their code.
 *
 * New methods on ExtendedPropertyReflection will be added
 * in minor versions.
 *
 * @api
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

	public function isVirtual(): TrinaryLogic;

	/**
	 * @param self::HOOK_* $hookType
	 */
	public function hasHook(string $hookType): bool;

	/**
	 * @param self::HOOK_* $hookType
	 */
	public function getHook(string $hookType): ExtendedMethodReflection;

	public function isProtectedSet(): bool;

	public function isPrivateSet(): bool;

	/**
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

}
