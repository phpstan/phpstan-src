<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Reflection\ClassReflection;

/**
 * What DependencyResolver::resolveFileDependencies() resolved the dependencies of an analysed file to.
 */
final class ResolvedDependencies
{

	/**
	 * @param list<string> $fileDependencies
	 * @param list<string> $usedTraitFileDependencies
	 * @param list<string> $packageDependencies
	 * @param list<ClassReflection> $classReflections every class depended on, with its ancestors
	 */
	public function __construct(
		private array $fileDependencies,
		private array $usedTraitFileDependencies,
		private array $packageDependencies,
		private array $classReflections,
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

	/**
	 * @return list<string>
	 */
	public function getUsedTraitFileDependencies(): array
	{
		return $this->usedTraitFileDependencies;
	}

	/**
	 * @return list<string>
	 */
	public function getPackageDependencies(): array
	{
		return $this->packageDependencies;
	}

	/**
	 * @return list<ClassReflection>
	 */
	public function getClassReflections(): array
	{
		return $this->classReflections;
	}

}
