<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use function array_slice;
use function explode;
use function str_starts_with;

#[AutowiredService]
final class RestrictedInternalUsageHelper
{

	public function shouldClassBeReported(Scope $scope, ClassReflection $classReflection): bool
	{
		return $this->shouldBeReported($scope, $classReflection->getName());
	}

	public function shouldFunctionBeReported(Scope $scope, FunctionReflection $functionReflection): bool
	{
		return $this->shouldBeReported($scope, $functionReflection->getName());
	}

	public function getRootNamespace(ClassReflection $classReflection): ?string
	{
		return $this->getRootNamespaceFromName($classReflection->getName());
	}

	private function shouldBeReported(Scope $scope, string $name): bool
	{
		$currentNamespace = $scope->getNamespace();
		if ($currentNamespace === null) {
			$classReflection = $scope->getClassReflection();
			if ($classReflection === null) {
				return true;
			}

			return $classReflection->getName() !== $name;
		}

		$currentNamespace = explode('\\', $currentNamespace)[0];
		$namespace = $this->getRootNamespaceFromName($name);

		return !str_starts_with($namespace . '\\', $currentNamespace . '\\');
	}

	private function getRootNamespaceFromName(string $name): ?string
	{
		return array_slice(explode('\\', $name), 0, -1)[0] ?? null;
	}

}
