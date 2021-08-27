<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Mixin;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\TypeUtils;

class MixinMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var string[] */
	private array $mixinExcludeClasses;

	/**
	 * @param string[] $mixinExcludeClasses
	 */
	public function __construct(array $mixinExcludeClasses)
	{
		$this->mixinExcludeClasses = $mixinExcludeClasses;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->findMethod($classReflection, $methodName) !== null;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$method = $this->findMethod($classReflection, $methodName);
		if ($method === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $method;
	}

	private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
	{
		$mixinTypes = $classReflection->getResolvedMixinTypes();
		foreach ($mixinTypes as $type) {
			if (count(array_intersect(TypeUtils::getDirectClassNames($type), $this->mixinExcludeClasses)) > 0) {
				continue;
			}

			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			$method = $type->getMethod($methodName, new OutOfClassScope());
			$static = $method->isStatic();
			if (
				!$static
				&& $classReflection->hasNativeMethod('__callStatic')
			) {
				$static = true;
			}

			return new MixinMethodReflection($method, $static);
		}

		foreach ($classReflection->getParents() as $parentClass) {
			$method = $this->findMethod($parentClass, $methodName);
			if ($method === null) {
				continue;
			}

			return $method;
		}

		return null;
	}

}
