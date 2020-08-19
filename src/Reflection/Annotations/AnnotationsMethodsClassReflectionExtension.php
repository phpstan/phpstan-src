<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeHelper;

class AnnotationsMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var MethodReflection[][] */
	private array $methods = [];

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (!isset($this->methods[$classReflection->getCacheKey()])) {
			$this->methods[$classReflection->getCacheKey()] = $this->createMethods($classReflection, $classReflection);
		}

		return isset($this->methods[$classReflection->getCacheKey()][$methodName]);
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return $this->methods[$classReflection->getCacheKey()][$methodName];
	}

	/**
	 * @param ClassReflection $classReflection
	 * @param ClassReflection $declaringClass
	 * @return MethodReflection[]
	 */
	private function createMethods(
		ClassReflection $classReflection,
		ClassReflection $declaringClass
	): array
	{
		$methods = [];
		foreach ($classReflection->getTraits() as $traitClass) {
			$methods += $this->createMethods($traitClass, $classReflection);
		}
		foreach ($classReflection->getParents() as $parentClass) {
			$methods += $this->createMethods($parentClass, $parentClass);
			foreach ($parentClass->getTraits() as $traitClass) {
				$methods += $this->createMethods($traitClass, $parentClass);
			}
		}
		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$methods += $this->createMethods($interfaceClass, $interfaceClass);
		}

		$fileName = $classReflection->getFileName();
		if ($fileName === false) {
			return $methods;
		}

		$methodTags = $classReflection->getMethodTags();
		foreach ($methodTags as $methodName => $methodTag) {
			$parameters = [];
			foreach ($methodTag->getParameters() as $parameterName => $parameterTag) {
				$parameters[] = new AnnotationsMethodParameterReflection(
					$parameterName,
					$parameterTag->getType(),
					$parameterTag->passedByReference(),
					$parameterTag->isOptional(),
					$parameterTag->isVariadic(),
					$parameterTag->getDefaultValue()
				);
			}

			$methods[$methodName] = new AnnotationMethodReflection(
				$methodName,
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$methodTag->getReturnType(),
					$classReflection->getActiveTemplateTypeMap()
				),
				$parameters,
				$methodTag->isStatic(),
				$this->detectMethodVariadic($parameters)
			);
		}
		return $methods;
	}

	/**
	 * @param AnnotationsMethodParameterReflection[] $parameters
	 * @return bool
	 */
	private function detectMethodVariadic(array $parameters): bool
	{
		if ($parameters === []) {
			return false;
		}

		$possibleVariadicParameterIndex = count($parameters) - 1;
		$possibleVariadicParameter = $parameters[$possibleVariadicParameterIndex];

		return $possibleVariadicParameter->isVariadic();
	}

}
