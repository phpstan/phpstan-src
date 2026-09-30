<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

/**
 * The interface DependencyEmitter can be typehinted in 2nd parameter of Rule::processNode(),
 * and in the Scope parameter of dynamic return type extensions and expression type resolver
 * extensions:
 *
 * ```php
 * /**
 *  * @param Scope&DependencyEmitter $scope
 *  *\/
 * public function processNode(Node $node, Scope $scope): array
 * ```
 *
 * The intersection goes to the PHPDoc: the native parameter type stays Scope, which is what the
 * interfaces declare once PHPStan is downgraded for older PHP versions.
 *
 * It declares what the analysis of the current file depends on besides the analysed code, so
 * that the result cache re-analyses the file when that changes - see ResultCacheValueExtension.
 *
 * @api
 */
interface DependencyEmitter
{

	/**
	 * The analysis of the current file depends on the value $extensionClass gives for $key.
	 *
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function valueDependency(string $extensionClass, string $key): void;

}
