<?php

namespace AllowedSubTypesClassReflectionExtensionTest;

use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use function PHPStan\Testing\assertType;

class Foo {}
class Bar extends Foo {}
class Baz extends Foo {}
class Qux extends Foo {}

class Extension implements AllowedSubTypesClassReflectionExtension
{
	public function supports(ClassReflection $classReflection): bool
	{
		return $classReflection->getName() === 'AllowedSubTypesClassReflectionExtensionTest\\Foo';
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		return [
			new ObjectType('AllowedSubTypesClassReflectionExtensionTest\\Bar'),
			new ObjectType('AllowedSubTypesClassReflectionExtensionTest\\Baz'),
			new ObjectType('AllowedSubTypesClassReflectionExtensionTest\\Qux'),
		];
	}
}

function acceptsFoo(Foo $foo): void {
	assertType('AllowedSubTypesClassReflectionExtensionTest\\Foo', $foo);

	if ($foo instanceof Bar) {
		return;
	}

	assertType('AllowedSubTypesClassReflectionExtensionTest\\Foo~AllowedSubTypesClassReflectionExtensionTest\\Bar', $foo);

	if ($foo instanceof Qux) {
		return;
	}

	assertType('AllowedSubTypesClassReflectionExtensionTest\\Baz', $foo);
}
