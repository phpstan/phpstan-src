<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionExtension;
use ReflectionFunction;
use ReflectionGenerator;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionZendExtension;
use function array_keys;
use function in_array;
use function sprintf;
use function str_starts_with;

#[AutowiredService]
final class RuntimeReflectionInstantiationUsageExtension implements RestrictedClassNameUsageExtension
{

	public function isRestrictedClassNameUsage(
		ClassReflection $classReflection,
		Scope $scope,
		ClassNameUsageLocation $location,
	): ?RestrictedUsage
	{
		if ($location->value !== ClassNameUsageLocation::INSTANTIATION) {
			return null;
		}

		if (!$scope->isInClass()) {
			return null;
		}

		if (!in_array($classReflection->getName(), [
			ReflectionMethod::class,
			ReflectionClass::class,
			ReflectionClassConstant::class,
			'ReflectionEnum',
			'ReflectionEnumBackedCase',
			ReflectionZendExtension::class,
			ReflectionExtension::class,
			ReflectionFunction::class,
			ReflectionObject::class,
			ReflectionParameter::class,
			ReflectionProperty::class,
			ReflectionGenerator::class,
			'ReflectionFiber',
		], true)) {
			return null;
		}

		$scopeClassReflection = $scope->getClassReflection();
		$hasPhpStanInterface = false;
		foreach (array_keys($scopeClassReflection->getInterfaces()) as $interfaceName) {
			if (!str_starts_with($interfaceName, 'PHPStan\\')) {
				continue;
			}

			$hasPhpStanInterface = true;
		}

		if (!$hasPhpStanInterface) {
			return null;
		}

		return RestrictedUsage::create(
			sprintf('Creating new %s is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.', $classReflection->getName()),
			'phpstanApi.runtimeReflection',
		);
	}

}
