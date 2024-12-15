<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;

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

	public function isAbstract(): TrinaryLogic;

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

}
