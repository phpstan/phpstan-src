<?php declare(strict_types = 1);

namespace PHPStan\Reflection\RequireExtension;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\StaticType;
use PHPStan\Type\TypeCombinator;

// autoTag: false - wired explicitly in ClassReflectionExtensionRegistry, must not be tagged
#[AutowiredService(autoTag: false)]
final class RequireExtendsMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->findMethod($classReflection, $classReflection, $methodName) !== null;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): ExtendedMethodReflection
	{
		$method = $this->findMethod($classReflection, $classReflection, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

	private function findMethod(ClassReflection $originalClassReflection, ClassReflection $classReflection, string $methodName): ?ExtendedMethodReflection
	{
		if (!$classReflection->isInterface()) {
			return null;
		}

		$extendsTags = $classReflection->getRequireExtendsTags();
		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();

			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			// map static to static(interface)&Base so that it gets resolved against the type the method is called on
			return $type->getUnresolvedMethodPrototype($methodName, new OutOfClassScope())
				->withCalledOnType(TypeCombinator::intersect(new StaticType($originalClassReflection), $type))
				->getTransformedMethod();
		}

		$interfaces = $classReflection->getInterfaces();
		foreach ($interfaces as $interface) {
			$method = $this->findMethod($originalClassReflection, $interface, $methodName);
			if ($method !== null) {
				return $method;
			}
		}

		return null;
	}

}
