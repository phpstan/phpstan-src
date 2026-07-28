<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[AutowiredService]
final class PropertyDescriptor
{

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	public function describeProperty(ExtendedPropertyReflection $property, Scope $scope, $propertyFetch): string
	{
		if ($propertyFetch instanceof Node\Expr\PropertyFetch) {
			$fetchedOnType = $scope->getType($propertyFetch->var);
			$declaringClassType = new ObjectType($property->getDeclaringClass()->getName());
			if ($declaringClassType->isSuperTypeOf($fetchedOnType)->yes()) {
				$classDescription = $property->getDeclaringClass()->getDisplayName();
			} else {
				$classDescription = $fetchedOnType->describe(VerbosityLevel::typeOnly());
			}
		} else {
			$classDescription = $property->getDeclaringClass()->getDisplayName();
		}

		// the fetch name node is not usable for dynamic accesses like $foo->{$name}
		$name = $property->getName();
		if (!$property->isStatic()) {
			return sprintf('Property %s::$%s', $classDescription, $name);
		}

		return sprintf('Static property %s::$%s', $classDescription, $name);
	}

}
