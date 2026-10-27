<?php declare(strict_types = 1);

namespace ResultCacheCollectedDataRulesTracking;

use PHPStan\Analyser\DeclarationDependencyTracker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\StringType;
use function file;
use function in_array;

/**
 * MoreViews has a method for each view listed in a file that is not analysed. A class reflection
 * extension gets no Scope, so it tracks the file on the class.
 */
final class MoreViewsExtension implements MethodsClassReflectionExtension
{

	public function __construct(
		private DeclarationDependencyTracker $declarationDependencyTracker,
		private string $moreViewsFile,
	)
	{
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if ($classReflection->getName() !== MoreViews::class) {
			return false;
		}

		$this->declarationDependencyTracker->trackFileDependency($classReflection, $this->moreViewsFile);

		return in_array($methodName, file($this->moreViewsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), true);
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new ViewMethodReflection($classReflection, $methodName, new StringType());
	}

}
