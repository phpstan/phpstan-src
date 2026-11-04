<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Type;
use function count;

/**
 * Members of built-in classes (and of classes extending them) come from stubs filtered for the analysed PHP version.
 * When the scope is narrowed (e.g. by a `PHP_VERSION_ID` check) to PHP versions
 * that exclude the analysed one, a member missing from the stubs might exist there.
 */
#[AutowiredService]
final class BuiltinClassMemberAvailabilityHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function mightBeAvailableInScopePhpVersion(Scope $scope, Type $classType): bool
	{
		$classNames = $classType->getObjectClassNames();
		if (count($classNames) === 0) {
			return false;
		}

		foreach ($classNames as $className) {
			if (!$this->reflectionProvider->hasClass($className)) {
				return false;
			}

			if (!$this->hasBuiltinClassInHierarchy($this->reflectionProvider->getClass($className))) {
				return false;
			}
		}

		return $scope->getPhpVersion()->getType()->isSuperTypeOf(
			new ConstantIntegerType($this->phpVersion->getVersionId()),
		)->no();
	}

	private function hasBuiltinClassInHierarchy(ClassReflection $classReflection): bool
	{
		if ($classReflection->isBuiltin()) {
			return true;
		}

		foreach ($classReflection->getParents() as $parentReflection) {
			if ($parentReflection->isBuiltin()) {
				return true;
			}
		}

		return false;
	}

}
