<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use function array_slice;
use function explode;

#[AutowiredService]
final class RestrictedInternalUsageHelper
{

	public function shouldFunctionBeReported(Scope $scope, FunctionReflection $functionReflection): bool
	{
		$functionName = $functionReflection->getName();
		$currentNamespace = $scope->getNamespace();
		if ($currentNamespace === null) {
			$scopeFunctionReflection = $scope->getFunction();
			if ($scopeFunctionReflection === null || $scope->isInClass()) {
				return true;
			}

			return $scopeFunctionReflection->getName() !== $functionName;
		}

		return $this->getRootNamespaceFromName($functionName) !== explode('\\', $currentNamespace)[0];
	}

	public function shouldClassBeReported(Scope $scope, ClassReflection $classReflection): bool
	{
		$scopeClassReflection = $scope->getClassReflection();
		if ($scopeClassReflection !== null && $scopeClassReflection->getName() === $classReflection->getName()) {
			return false;
		}

		$currentNamespace = $scope->getNamespace();
		if ($currentNamespace === null) {
			return true;
		}

		return $this->getRootNamespace($classReflection) !== explode('\\', $currentNamespace)[0];
	}

	public function getRootNamespace(ClassReflection $classReflection): ?string
	{
		$namespace = $classReflection->getNamespaceName();
		if ($namespace === null) {
			return null;
		}

		return explode('\\', $namespace)[0];
	}

	private function getRootNamespaceFromName(string $name): ?string
	{
		return array_slice(explode('\\', $name), 0, -1)[0] ?? null;
	}

}
