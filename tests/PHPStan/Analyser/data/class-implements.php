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
		string $className,
		bool $bool,
		mixed $mixed,
	): void {
		assertType('array<string, class-string>', class_implements($object));
		assertType('array<string, class-string>', class_implements($objectOrClassString));
		assertType('array<string, class-string>|false', class_implements($objectOrString));
		assertType('array<string, class-string>', class_implements($classString));
		assertType('array<string, class-string>|false', class_implements($className));

		assertType('array<string, class-string>', class_implements($object, true));
		assertType('array<string, class-string>', class_implements($objectOrClassString, true));
		assertType('array<string, class-string>|false', class_implements($objectOrString, true));
		assertType('array<string, class-string>', class_implements($classString, true));
		assertType('array<string, class-string>|false', class_implements($className, true));

		assertType('array<string, class-string>', class_implements($object, false));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_implements($objectOrString, false));
		assertType('array<string, class-string>|false', class_implements($classString, false));
		assertType('array<string, class-string>|false', class_implements($className, false));

		assertType('array<string, class-string>', class_implements($object, $bool));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, $bool));
		assertType('array<string, class-string>|false', class_implements($objectOrString, $bool));
		assertType('array<string, class-string>|false', class_implements($classString, $bool));
		assertType('array<string, class-string>|false', class_implements($className, $bool));

		assertType('array<string, class-string>', class_implements($object, $mixed));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, $mixed));
		assertType('array<string, class-string>|false', class_implements($objectOrString, $mixed));
		assertType('array<string, class-string>|false', class_implements($classString, $mixed));
		assertType('array<string, class-string>|false', class_implements($className, $mixed));

		assertType('array<string, string>', class_uses($object));
		assertType('array<string, string>', class_uses($objectOrClassString));
		assertType('array<string, string>|false', class_uses($objectOrString));
		assertType('array<string, string>', class_uses($classString));
		assertType('array<string, string>|false', class_uses($className));

		assertType('array<string, string>', class_uses($object, true));
		assertType('array<string, string>', class_uses($objectOrClassString, true));
		assertType('array<string, string>|false', class_uses($objectOrString, true));
		assertType('array<string, string>', class_uses($classString, true));
		assertType('array<string, string>|false', class_uses($className, true));

		assertType('array<string, string>', class_uses($object, false));
		assertType('array<string, string>|false', class_uses($objectOrClassString, false));
		assertType('array<string, string>|false', class_uses($objectOrString, false));
		assertType('array<string, string>|false', class_uses($classString, false));
		assertType('array<string, string>|false', class_uses($className, false));

		assertType('array<string, string>', class_uses($object, $bool));
		assertType('array<string, string>|false', class_uses($objectOrClassString, $bool));
		assertType('array<string, string>|false', class_uses($objectOrString, $bool));
		assertType('array<string, string>|false', class_uses($classString, $bool));
		assertType('array<string, string>|false', class_uses($className, $bool));

		assertType('array<string, string>', class_uses($object, $mixed));
		assertType('array<string, string>|false', class_uses($objectOrClassString, $mixed));
		assertType('array<string, string>|false', class_uses($objectOrString, $mixed));
		assertType('array<string, string>|false', class_uses($classString, $mixed));
		assertType('array<string, string>|false', class_uses($className, $mixed));

		assertType('array<string, class-string>', class_parents($object));
		assertType('array<string, class-string>', class_parents($objectOrClassString));
		assertType('array<string, class-string>|false', class_parents($objectOrString));
		assertType('array<string, class-string>', class_parents($classString));
		assertType('array<string, class-string>|false', class_parents($className));

		assertType('array<string, class-string>', class_parents($object, true));
		assertType('array<string, class-string>', class_parents($objectOrClassString, true));
		assertType('array<string, class-string>|false', class_parents($objectOrString, true));
		assertType('array<string, class-string>', class_parents($classString, true));
		assertType('array<string, class-string>|false', class_parents($className, true));

		assertType('array<string, class-string>', class_parents($object, false));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_parents($objectOrString, false));
		assertType('array<string, class-string>|false', class_parents($classString, false));
		assertType('array<string, class-string>|false', class_parents($className, false));

		assertType('array<string, class-string>', class_parents($object, $bool));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, $bool));
		assertType('array<string, class-string>|false', class_parents($objectOrString, $bool));
		assertType('array<string, class-string>|false', class_parents($classString, $bool));
		assertType('array<string, class-string>|false', class_parents($className, $bool));

		assertType('array<string, class-string>', class_parents($object, $mixed));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, $mixed));
		assertType('array<string, class-string>|false', class_parents($objectOrString, $mixed));
		assertType('array<string, class-string>|false', class_parents($classString, $mixed));
		assertType('array<string, class-string>|false', class_parents($className, $mixed));
	}

}
