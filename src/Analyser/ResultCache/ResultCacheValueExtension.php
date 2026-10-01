<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * A value outside of the analysed code that rules and extensions read - whether a service
 * exists in a DI container, the value of a configuration parameter, the contents of a file.
 *
 * A rule or an extension reading such a value declares it:
 *
 * ```php
 * $scope->trackValueDependency(MyExtension::class, $key);
 * ```
 *
 * The result cache then records the value, and re-analyses the files that declared it when it
 * is different, and only them. Compared to ResultCacheMetaExtension, which discards the whole
 * result cache on any change, only what was actually asked about matters, and only for the
 * files asking.
 *
 * To register it in the configuration file use the `phpstan.resultCacheValueExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.resultCacheValueExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::EXTENSION_TAG)]
interface ResultCacheValueExtension
{

	public const EXTENSION_TAG = 'phpstan.resultCacheValueExtension';

	/**
	 * The current value for the key - compared with the one recorded by the last analysis.
	 * A long value is better hashed.
	 */
	public function getValue(string $key): string;

	/**
	 * The key as the result cache stores it. A key that is a file path is best stored relative to
	 * the project, so that the result cache survives a moved checkout.
	 */
	public function keyToResultCache(string $key): string;

	/**
	 * The key back from the result cache - the reverse of keyToResultCache().
	 */
	public function keyFromResultCache(string $storedKey): string;

}
