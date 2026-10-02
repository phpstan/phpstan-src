<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

/**
 * The interface DependencyTracker can be typehinted in 2nd parameter of Rule::processNode() and
 * Collector::processNode(), and in the Scope parameter of dynamic return type extensions, dynamic
 * throw type extensions, expression type resolver extensions, parameter out type extensions,
 * parameter closure type extensions, parameter closure this extensions and type-specifying
 * extensions:
 *
 * ```php
 * /**
 *  * @param Scope&DependencyTracker $scope
 *  *\/
 * public function processNode(Node $node, Scope $scope): array
 * ```
 *
 * The intersection goes to the PHPDoc: the native parameter type stays Scope, which is what the
 * interfaces declare once PHPStan is downgraded for older PHP versions.
 *
 * It tracks what the analysis of the current file depends on besides the analysed code, so that
 * the result cache re-analyses the file when that changes - see ResultCacheValueExtension.
 *
 * Extensions that get no Scope because they describe a class - class reflection extensions - use
 * DeclarationDependencyTracker instead.
 *
 * @api
 */
interface DependencyTracker
{

	/**
	 * The analysis of the current file depends on the value $extensionClass gives for $key.
	 *
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function trackValueDependency(string $extensionClass, string $key): void;

	/**
	 * The analysis of the current file depends on the contents of $file - a data file, a template,
	 * a docblock in another PHP file - that is read on its own, without PHPStan knowing about it.
	 * The current file is then re-analysed whenever $file is created, changed in any way, or deleted.
	 *
	 * The path should be absolute. The file does not have to exist.
	 */
	public function trackFileDependency(string $file): void;

	/**
	 * The analysis of the current file depends on the files in $directory, recursively, whose names
	 * match $pattern (fnmatch() syntax, like "*.php" or "Pest.php") - a directory scanned for
	 * configuration files, templates or migrations. The current file is then re-analysed whenever such
	 * a file is created, changed in any way, deleted or renamed, or the directory itself is created or
	 * deleted.
	 */
	public function trackDirectoryDependency(string $directory, string $pattern = '*'): void;

	/**
	 * The analysis of the current file depends on the class $className as if the code referenced it -
	 * a class named in a string, in a PHPDoc tag PHPStan does not resolve, or in a configuration file.
	 * The current file is then re-analysed when the class or one of its parents, interfaces or traits
	 * changes what it declares (signatures and PHPDocs, not method bodies), and when the class is
	 * created, deleted or moved to another file.
	 */
	public function trackClassDependency(string $className): void;

}
