<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * This is the extension interface to implement if you want to dynamically
 * load stub files based on your logic. As opposed to simply list them in the configuration file.
 *
 * To register it in the configuration file use the `phpstan.stubFilesExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.stubFilesExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::EXTENSION_TAG)]
interface StubFilesExtension
{

	public const EXTENSION_TAG = 'phpstan.stubFilesExtension';

	/** @return string[] */
	public function getFiles(): array;

}
