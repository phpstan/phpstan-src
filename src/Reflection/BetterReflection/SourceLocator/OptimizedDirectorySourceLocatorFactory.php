<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

interface OptimizedDirectorySourceLocatorFactory
{

	public function create(string $directory): OptimizedDirectorySourceLocator;

}
