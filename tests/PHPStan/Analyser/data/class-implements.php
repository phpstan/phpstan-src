<?php

namespace ClassImplements;

use function PHPStan\Testing\assertType;

class ClassImplements
{
	/**
	 * @param object|class-string $objectOrClassString
	 * @param class-string $classString
	 */
	public function test(
		object $object,
		object|string $objectOrClassString,
		object|string $objectOrString,
		string $classString,
		string $className
	): void {
		assertType('array<string, class-string>', class_implements($object));
		assertType('array<string, class-string>', class_implements($objectOrClassString));
		assertType('array<string, class-string>|false', class_implements($objectOrString));
		assertType('array<string, class-string>', class_implements($classString));
		assertType('array<string, class-string>|false', class_implements($className));

		assertType('array<string, class-string>', class_implements($object, false));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_implements($objectOrString, false));
		assertType('array<string, class-string>|false', class_implements($classString, false));
		assertType('array<string, class-string>|false', class_implements($className, false));
	}

}
