<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

/**
 * ResultCacheMetaExtension can be used for extending PHPStan's built-in mechanism that is used for
 * calculating metadata for result cache. Using this extension you may add additional metadata that will
 * be used for determining if analysis must be run again, or can be re-used from result cache.
 *
 * @see https://github.com/phpstan/phpstan-symfony/issues/255 for the context.
 *
 * To register it in the configuration file use the `phpstan.resultCacheMetaExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.resultCacheMetaExtension
 * ```
 *
 * @api
 */
interface ResultCacheMetaExtension
{

	public const EXTENSION_TAG = 'phpstan.resultCacheMetaExtension';

	/**
	 * Returns unique key for this result cache meta entry. This describes the source of the metadata.
	 */
	public function getKey(): string;

	/**
	 * Returns hash of the result cache meta entry. This represents the current state of the additional meta source.
	 */
	public function getHash(): string;

}
