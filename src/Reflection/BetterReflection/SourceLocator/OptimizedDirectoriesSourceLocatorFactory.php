<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

interface OptimizedDirectoriesSourceLocatorFactory
{

	/**
	 * @param string[] $directories
	 * @return \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectoriesSourceLocator
	 */
	public function create(array $directories): OptimizedDirectoriesSourceLocator;

}
