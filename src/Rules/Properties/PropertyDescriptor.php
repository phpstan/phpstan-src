<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Reflection\PropertyReflection;
use function sprintf;

class PropertyDescriptor
{

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	public function describeProperty(PropertyReflection $property, $propertyFetch): string
	{
		/** @var Node\Identifier $name */
		$name = $propertyFetch->name;
		if (!$property->isStatic()) {
			return sprintf('Property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $name->name);
		}

		return sprintf('Static property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $name->name);
	}

}
