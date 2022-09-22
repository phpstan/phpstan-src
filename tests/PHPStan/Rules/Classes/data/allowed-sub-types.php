<?php

namespace AllowedSubTypes;

use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

class Foo {}
class Bar extends Foo {}
class Baz extends Foo {}
class Qux extends Bar {}

class Extension implements AllowedSubTypesClassReflectionExtension
{
	public function supports(ClassReflection $classReflection): bool
	{
		return $classReflection->getName() === 'AllowedSubTypes\\Foo';
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		return [
			new ObjectType('AllowedSubTypes\\Bar'),
		];
	}
}
