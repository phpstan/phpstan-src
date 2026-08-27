<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * Calculates the current hash of an extension-defined semantic dependency.
 *
 * Register implementations with the `phpstan.resultCacheDependencyExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyResultCacheDependencyExtension
 *		tags:
 *			- phpstan.resultCacheDependencyExtension
 * ```
 *
 * Rules can associate a dependency with the file currently being analysed by emitting a record from
 * Rule::processNode(). Keep the native parameter type as Scope and declare
 * `@param Scope&CollectedDataEmitter $scope` in the method PHPDoc:
 *
 * ```php
 * public function processNode(Node $node, Scope $scope): array
 * {
 *     $scope->emitCollectedData(
 *         ResultCacheDependencyCollector::class,
 *         ResultCacheDependencyCollector::createData($this->extension, $dependencyKey),
 *     );
 *
 *     return [];
 * }
 * ```
 *
 * Emit dependencies only from Rule::processNode(). Other extension callbacks, including dynamic return
 * type extensions, can receive scopes without an active collected-data callback.
 * Repeated emissions of the same provider and dependency key for one file are deduplicated.
 *
 * If a cached hash changes, PHPStan reanalyses only files that emitted the dependency, not their ordinary
 * PHPStan dependants. Each affected file must emit its own dependency. Use ResultCacheMetaExtension
 * instead for state with global or indirect effects.
 *
 * @api
 */
#[ExtensionInterface(tag: self::EXTENSION_TAG)]
interface ResultCacheDependencyExtension
{

	public const EXTENSION_TAG = 'phpstan.resultCacheDependencyExtension';

	/**
	 * Returns a globally unique, stable key identifying this dependency provider.
	 *
	 * The implementation class name (self::class) is recommended. Multiple instances of the same class
	 * need distinct keys. The key must be stable across all analysis processes. Changing it makes previously
	 * cached records unknown and reanalyses their files.
	 */
	public function getKey(): string;

	/**
	 * Returns a deterministic hash of the dependency identified by the opaque key.
	 *
	 * The key can come from an older result cache, so obsolete keys must be handled deterministically.
	 *
	 * The hash must describe the same state used during analysis, and that state must remain stable for
	 * the duration of the run.
	 *
	 * Restoring happens before configured bootstrapFiles are executed in the main process, while saving
	 * happens afterwards. The hash must not depend on state initialized by bootstrapFiles.
	 *
	 * Calls can be repeated and can happen in any order or process. They must return the same hash while
	 * the backing state is unchanged.
	 */
	public function getHash(string $dependencyKey): string;

}
