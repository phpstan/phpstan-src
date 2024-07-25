<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\SimpleXMLElementProperty;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;

final class SimpleXMLElementClassPropertyReflectionExtension implements PropertiesClassReflectionExtension
{

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $classReflection->getName() === 'SimpleXMLElement' || $classReflection->isSubclassOf('SimpleXMLElement');
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		return new SimpleXMLElementProperty($classReflection, new BenevolentUnionType([new ObjectType($classReflection->getName()), new NullType()]));
	}

}
