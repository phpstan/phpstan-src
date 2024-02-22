<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;

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

			$templateTypeScope = TemplateTypeScope::createWithClass($classReflection->getName());

			$templateTypeMap = new TemplateTypeMap(array_map(
				static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag),
				$methodTags[$methodName]->getTemplates()
			));

			$isStatic = $methodTags[$methodName]->isStatic();
			$nativeCallMethodName = $isStatic ? '__callStatic' : '__call';

			return new AnnotationMethodReflection(
				$methodName,
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$methodTags[$methodName]->getReturnType(),
					$classReflection->getActiveTemplateTypeMap(),
					$classReflection->getCallSiteVarianceMap(),
					TemplateTypeVariance::createCovariant(),
				),
				$parameters,
				$isStatic,
				$this->detectMethodVariadic($parameters),
				$classReflection->hasNativeMethod($nativeCallMethodName)
					? $classReflection->getNativeMethod($nativeCallMethodName)->getThrowType()
					: null,
				$templateTypeMap,
			);
		}

		foreach ($classReflection->getTraits() as $traitClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithMethod($traitClass, $classReflection, $methodName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		$parentClass = $classReflection->getParentClass();
		while ($parentClass !== null) {
			$methodWithDeclaringClass = $this->findClassReflectionWithMethod($parentClass, $parentClass, $methodName);
			if ($methodWithDeclaringClass !== null) {
				return $methodWithDeclaringClass;
			}

			foreach ($parentClass->getTraits() as $traitClass) {
				$parentTraitMethodWithDeclaringClass = $this->findClassReflectionWithMethod($traitClass, $parentClass, $methodName);
				if ($parentTraitMethodWithDeclaringClass === null) {
					continue;
				}

				return $parentTraitMethodWithDeclaringClass;
			}

			$parentClass = $parentClass->getParentClass();
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
