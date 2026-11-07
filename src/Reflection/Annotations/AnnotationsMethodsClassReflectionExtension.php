<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use function array_key_exists;
use function array_map;
use function count;

// autoTag: false - wired explicitly in ClassReflectionExtensionRegistry, must not be tagged
#[AutowiredService(autoTag: false)]
final class AnnotationsMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var array<string, array<string, ExtendedMethodReflection|null>> */
	private array $methods = [];

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->findMethod($classReflection, $methodName) !== null;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): ExtendedMethodReflection
	{
		$method = $this->findMethod($classReflection, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

	/**
	 * Remembers misses too: the lookup recurses into the parent and every
	 * interface, which reach the same ancestors many times over.
	 */
	private function findMethod(ClassReflection $classReflection, string $methodName): ?ExtendedMethodReflection
	{
		$cacheKey = $classReflection->getCacheKey();
		if (!array_key_exists($methodName, $this->methods[$cacheKey] ?? [])) {
			$this->methods[$cacheKey][$methodName] = $this->findClassReflectionWithMethod($classReflection, $classReflection, $methodName);
		}

		return $this->methods[$cacheKey][$methodName];
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
				$methodTags[$methodName]->getTemplateTags(),
			));

			$isStatic = $methodTags[$methodName]->isStatic();
			$nativeCallMethodName = $isStatic ? '__callStatic' : '__call';
			$nativeCallMethod = $classReflection->hasNativeMethod($nativeCallMethodName)
				? $classReflection->getNativeMethod($nativeCallMethodName)
				: null;
			$classResolvedPhpDoc = $classReflection->getResolvedPhpDoc();
			$isPure = null;
			if ($nativeCallMethod !== null) {
				$nativePurity = $nativeCallMethod->isPure();
				$isPure = $nativePurity->yes() ? true : ($nativePurity->no() ? false : null);
			} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsPure()) {
				$isPure = true;
			} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsImpure()) {
				$isPure = false;
			}

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
				$nativeCallMethod !== null ? $nativeCallMethod->getThrowType() : null,
				$templateTypeMap,
				$isPure,
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
		if ($parentClass !== null) {
			$methodWithDeclaringClass = $this->findMethod($parentClass, $methodName);
			if ($methodWithDeclaringClass !== null) {
				return $methodWithDeclaringClass;
			}
		}

		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$methodWithDeclaringClass = $this->findMethod($interfaceClass, $methodName);
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
