<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * This is the extension interface to implement if you want to dynamically
 * mark methods as constructor. As opposed to simply list them in the configuration file.
 *
 * To register it in the configuration file use the `phpstan.additionalConstructorsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.additionalConstructorsExtension
 * ```
 *
 * @api
 */
interface AdditionalConstructorsExtension
{

	public const EXTENSION_TAG = 'phpstan.additionalConstructorsExtension';

	/** @return string[] */
	public function getAdditionalConstructors(ClassReflection $classReflection): array;

}
