<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

final class PropertyDescriptor
{

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	public function describeProperty(PropertyReflection $property, Scope $scope, $propertyFetch): string
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

		/** @var Node\Identifier $name */
		$name = $propertyFetch->name;
		if (!$property->isStatic()) {
			return sprintf('Property %s::$%s', $classDescription, $name->name);
		}

		return sprintf('Static property %s::$%s', $classDescription, $name->name);
	}

}
