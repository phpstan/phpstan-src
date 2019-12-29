<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

interface OptimizedSingleFileSourceLocatorFactory
{

	public function create(string $fileName): OptimizedSingleFileSourceLocator;

}
