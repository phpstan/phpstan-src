<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\Reflection\ClassReflection;

/**
 * DependencyTracker for extensions that get no Scope because they describe a class rather than
 * analyse code - class reflection extensions adding magic methods and properties, for example. Inject
 * it into the extension's constructor.
 *
 * What PHPStan learns about a class this way is remembered and reused by every file analysed after
 * it, so it's the class that depends on what the extension read, not the file being analysed: each
 * method declares the dependency of $classReflection. When it changes, every file depending on the
 * class - referencing it, calling its methods, reading its properties - is analysed again.
 *
 * @api
 */
interface DeclarationDependencyTracker
{

	/**
	 * What $classReflection declares depends on the value $extensionClass gives for $key - see
	 * DependencyTracker::trackValueDependency().
	 *
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function trackValueDependency(ClassReflection $classReflection, string $extensionClass, string $key): void;

	/**
	 * What $classReflection declares depends on the contents of $file - see
	 * DependencyTracker::trackFileDependency().
	 */
	public function trackFileDependency(ClassReflection $classReflection, string $file): void;

	/**
	 * What $classReflection declares depends on the files in $directory - see
	 * DependencyTracker::trackDirectoryDependency().
	 */
	public function trackDirectoryDependency(ClassReflection $classReflection, string $directory, string $pattern = '*'): void;

	/**
	 * What $classReflection declares depends on what the class $className declares - see
	 * DependencyTracker::trackClassDependency().
	 */
	public function trackClassDependency(ClassReflection $classReflection, string $className): void;

}
