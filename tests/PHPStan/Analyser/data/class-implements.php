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

		assertType('array<string, string>', class_uses($object));
		assertType('array<string, string>', class_uses($objectOrClassString));
		assertType('array<string, string>|false', class_uses($objectOrString));
		assertType('array<string, string>', class_uses($classString));
		assertType('array<string, string>|false', class_uses($className));

		assertType('array<string, string>', class_uses($object, false));
		assertType('array<string, string>|false', class_uses($objectOrClassString, false));
		assertType('array<string, string>|false', class_uses($objectOrString, false));
		assertType('array<string, string>|false', class_uses($classString, false));
		assertType('array<string, string>|false', class_uses($className, false));

		assertType('array<string, class-string>', class_parents($object));
		assertType('array<string, class-string>', class_parents($objectOrClassString));
		assertType('array<string, class-string>|false', class_parents($objectOrString));
		assertType('array<string, class-string>', class_parents($classString));
		assertType('array<string, class-string>|false', class_parents($className));

		assertType('array<string, class-string>', class_parents($object, false));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_parents($objectOrString, false));
		assertType('array<string, class-string>|false', class_parents($classString, false));
		assertType('array<string, class-string>|false', class_parents($className, false));
	}

}
