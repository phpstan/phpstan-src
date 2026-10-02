<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ClassResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\DirectoryResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;

#[AutowiredService(as: DeclarationDependencyTracker::class)]
final class ValueDependencyDeclarationDependencyTracker implements DeclarationDependencyTracker
{

	public function __construct(private ValueDependencyCollector $valueDependencyCollector)
	{
	}

	public function trackValueDependency(ClassReflection $classReflection, string $extensionClass, string $key): void
	{
		$this->valueDependencyCollector->recordForClass($classReflection->getName(), $extensionClass, $key);
	}

	public function trackFileDependency(ClassReflection $classReflection, string $file): void
	{
		$this->valueDependencyCollector->recordForClass($classReflection->getName(), FileResultCacheValueExtension::class, $this->valueDependencyCollector->getFileKey($file));
	}

	public function trackDirectoryDependency(ClassReflection $classReflection, string $directory, string $pattern = '*'): void
	{
		$this->valueDependencyCollector->recordForClass($classReflection->getName(), DirectoryResultCacheValueExtension::class, $this->valueDependencyCollector->getDirectoryKey($directory, $pattern));
	}

	public function trackClassDependency(ClassReflection $classReflection, string $className): void
	{
		$this->valueDependencyCollector->recordForClass($classReflection->getName(), ClassResultCacheValueExtension::class, ClassResultCacheValueExtension::createKey($className));
	}

}
