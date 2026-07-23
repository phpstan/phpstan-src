<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * This interface allows you to provide custom deprecation information
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyProvider
 *		tags:
 *			- phpstan.classDeprecationExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::CLASS_EXTENSION_TAG)]
interface ClassDeprecationExtension
{

	public const CLASS_EXTENSION_TAG = 'phpstan.classDeprecationExtension';

	public function getClassDeprecation(ReflectionClass|ReflectionEnum $reflection): ?Deprecation;

}
