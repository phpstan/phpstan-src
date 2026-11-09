<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function array_keys;
use function in_array;
use function sprintf;
use function str_starts_with;

#[AutowiredService]
final class RuntimeReflectionFunctionUsageExtension implements RestrictedFunctionUsageExtension
{

	public function isRestrictedFunctionUsage(
		FunctionReflection $functionReflection,
		Scope $scope,
	): ?RestrictedUsage
	{
		if (!$scope->isInClass()) {
			return null;
		}

		if (!in_array($functionReflection->getName(), [
			'is_a',
			'is_subclass_of',
			'class_parents',
			'class_implements',
			'class_uses',
		], true)) {
			return null;
		}

		$classReflection = $scope->getClassReflection();
		$hasPhpStanInterface = false;
		foreach (array_keys($classReflection->getInterfaces()) as $interfaceName) {
			if (!str_starts_with($interfaceName, 'PHPStan\\')) {
				continue;
			}

			$hasPhpStanInterface = true;
		}

		if (!$hasPhpStanInterface) {
			return null;
		}

		return RestrictedUsage::create(
			sprintf('Function %s() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.', $functionReflection->getName()),
			'phpstanApi.runtimeReflection',
		);
	}

}
