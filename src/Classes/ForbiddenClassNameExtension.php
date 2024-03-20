<?php declare(strict_types = 1);

namespace PHPStan\Classes;

/**
 * This is the extension interface to implement if you want to dynamically
 * add forbidden class prefixes to the ClassForbiddenNameCheck rule.
 *
 * The idea is that you want to report usages of classes that you're not supposed to use in application.
 * For example: Generated Doctrine proxies from their configured namespace.
 *
 * To register it in the configuration file use the `phpstan.forbiddenClassNamesExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.forbiddenClassNamesExtension
 * ```
 *
 * @api
 */
interface ForbiddenClassNameExtension
{

	public const EXTENSION_TAG = 'phpstan.forbiddenClassNamesExtension';

	/** @return array<string, string> */
	public function getClassPrefixes(): array;

}
