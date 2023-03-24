<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/**
 * This is the extension interface to implement if you want to described
 * allowed subtypes - to limit which classes can implement a certain interface
 * or extend a certain parent class.
 *
 * To register it in the configuration file use the `phpstan.broker.allowedSubTypesClassReflectionExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.allowedSubTypesClassReflectionExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/allowed-subtypes
 *
 * @api
 */
interface AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool;

	/**
	 * @return array<Type>
	 */
	public function getAllowedSubTypes(ClassReflection $classReflection): array;

}
