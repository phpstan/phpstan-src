<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeHelper;
use function count;

class AnnotationsMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var ExtendedMethodReflection[][] */
	private array $methods = [];

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (!isset($this->methods[$classReflection->getCacheKey()][$methodName])) {
			$method = $this->findClassReflectionWithMethod($classReflection, $classReflection, $methodName);
			if ($method === null) {
				return false;
			}
			$this->methods[$classReflection->getCacheKey()][$methodName] = $method;
		}

		return isset($this->methods[$classReflection->getCacheKey()][$methodName]);
	}

	/**
	 * @return ExtendedMethodReflection
	 */
	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return $this->methods[$classReflection->getCacheKey()][$methodName];
	}

	private function findClassReflectionWithMethod(
		ClassReflection $classReflection,
		ClassReflection $declaringClass,
		string $methodName,
	): ?ExtendedMethodReflection
	{
		$methodTags = $classReflection->getMethodTags();
		if (isset($methodTags[$methodName])) {
			$parameters = [];
			foreach ($methodTags[$methodName]->getParameters() as $parameterName => $parameterTag) {
				$parameters[] = new AnnotationsMethodParameterReflection(
					$parameterName,
					$parameterTag->getType(),
					$parameterTag->passedByReference(),
					$parameterTag->isOptional(),
					$parameterTag->isVariadic(),
					$parameterTag->getDefaultValue(),
				);
			}

			return new AnnotationMethodReflection(
				$methodName,
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$methodTags[$methodName]->getReturnType(),
					$classReflection->getActiveTemplateTypeMap(),
				),
				$parameters,
				$methodTags[$methodName]->isStatic(),
				$this->detectMethodVariadic($parameters),
			);
		}

		foreach ($classReflection->getTraits() as $traitClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithMethod($traitClass, $classReflection, $methodName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		foreach ($classReflection->getParents() as $parentClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithMethod($parentClass, $parentClass, $methodName);
			if ($methodWithDeclaringClass === null) {
				foreach ($parentClass->getTraits() as $traitClass) {
					$parentTraitMethodWithDeclaringClass = $this->findClassReflectionWithMethod($traitClass, $parentClass, $methodName);
					if ($parentTraitMethodWithDeclaringClass === null) {
						continue;
					}

					return $parentTraitMethodWithDeclaringClass;
				}
				continue;
			}

			return $methodWithDeclaringClass;
		}

		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithMethod($interfaceClass, $interfaceClass, $methodName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		return null;
	}

	/**
	 * @param AnnotationsMethodParameterReflection[] $parameters
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
