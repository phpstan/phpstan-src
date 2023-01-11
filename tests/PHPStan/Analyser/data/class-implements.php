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
		string $string,
		bool $bool,
		mixed $mixed,
	): void {
		assertType('array<string, class-string>', class_implements($object));
		assertType('(array<string, class-string>|false)', class_implements($objectOrClassString));
		assertType('array<string, class-string>|false', class_implements($objectOrString));
		assertType('(array<string, class-string>|false)', class_implements($classString));
		assertType('array<string, class-string>|false', class_implements($string));
		assertType('false', class_implements('thisIsNotAClass'));

		assertType('array<string, class-string>', class_implements($object, true));
		assertType('(array<string, class-string>|false)', class_implements($objectOrClassString, true));
		assertType('array<string, class-string>|false', class_implements($objectOrString, true));
		assertType('(array<string, class-string>|false)', class_implements($classString, true));
		assertType('array<string, class-string>|false', class_implements($string, true));
		assertType('false', class_implements('thisIsNotAClass', true));

		assertType('array<string, class-string>', class_implements($object, false));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_implements($objectOrString, false));
		assertType('array<string, class-string>|false', class_implements($classString, false));
		assertType('array<string, class-string>|false', class_implements($string, false));
		assertType('false', class_implements('thisIsNotAClass', false));

		assertType('array<string, class-string>', class_implements($object, $bool));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, $bool));
		assertType('array<string, class-string>|false', class_implements($objectOrString, $bool));
		assertType('array<string, class-string>|false', class_implements($classString, $bool));
		assertType('array<string, class-string>|false', class_implements($string, $bool));
		assertType('false', class_implements('thisIsNotAClass', $bool));

		assertType('array<string, class-string>', class_implements($object, $mixed));
		assertType('array<string, class-string>|false', class_implements($objectOrClassString, $mixed));
		assertType('array<string, class-string>|false', class_implements($objectOrString, $mixed));
		assertType('array<string, class-string>|false', class_implements($classString, $mixed));
		assertType('array<string, class-string>|false', class_implements($string, $mixed));
		assertType('false', class_implements('thisIsNotAClass', $mixed));

		assertType('array<string, class-string>', class_uses($object));
		assertType('(array<string, class-string>|false)', class_uses($objectOrClassString));
		assertType('array<string, class-string>|false', class_uses($objectOrString));
		assertType('(array<string, class-string>|false)', class_uses($classString));
		assertType('array<string, class-string>|false', class_uses($string));
		assertType('false', class_uses('thisIsNotAClass'));

		assertType('array<string, class-string>', class_uses($object, true));
		assertType('(array<string, class-string>|false)', class_uses($objectOrClassString, true));
		assertType('array<string, class-string>|false', class_uses($objectOrString, true));
		assertType('(array<string, class-string>|false)', class_uses($classString, true));
		assertType('array<string, class-string>|false', class_uses($string, true));
		assertType('false', class_uses('thisIsNotAClass', true));

		assertType('array<string, class-string>', class_uses($object, false));
		assertType('array<string, class-string>|false', class_uses($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_uses($objectOrString, false));
		assertType('array<string, class-string>|false', class_uses($classString, false));
		assertType('array<string, class-string>|false', class_uses($string, false));
		assertType('false', class_uses('thisIsNotAClass', false));

		assertType('array<string, class-string>', class_uses($object, $bool));
		assertType('array<string, class-string>|false', class_uses($objectOrClassString, $bool));
		assertType('array<string, class-string>|false', class_uses($objectOrString, $bool));
		assertType('array<string, class-string>|false', class_uses($classString, $bool));
		assertType('array<string, class-string>|false', class_uses($string, $bool));
		assertType('false', class_uses('thisIsNotAClass', $bool));

		assertType('array<string, class-string>', class_uses($object, $mixed));
		assertType('array<string, class-string>|false', class_uses($objectOrClassString, $mixed));
		assertType('array<string, class-string>|false', class_uses($objectOrString, $mixed));
		assertType('array<string, class-string>|false', class_uses($classString, $mixed));
		assertType('array<string, class-string>|false', class_uses($string, $mixed));
		assertType('false', class_uses('thisIsNotAClass', $mixed));

		assertType('array<string, class-string>', class_parents($object));
		assertType('(array<string, class-string>|false)', class_parents($objectOrClassString));
		assertType('array<string, class-string>|false', class_parents($objectOrString));
		assertType('(array<string, class-string>|false)', class_parents($classString));
		assertType('array<string, class-string>|false', class_parents($string));
		assertType('false', class_parents('thisIsNotAClass'));

		assertType('array<string, class-string>', class_parents($object, true));
		assertType('(array<string, class-string>|false)', class_parents($objectOrClassString, true));
		assertType('array<string, class-string>|false', class_parents($objectOrString, true));
		assertType('(array<string, class-string>|false)', class_parents($classString, true));
		assertType('array<string, class-string>|false', class_parents($string, true));
		assertType('false', class_parents('thisIsNotAClass', true));

		assertType('array<string, class-string>', class_parents($object, false));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, false));
		assertType('array<string, class-string>|false', class_parents($objectOrString, false));
		assertType('array<string, class-string>|false', class_parents($classString, false));
		assertType('array<string, class-string>|false', class_parents($string, false));
		assertType('false', class_parents('thisIsNotAClass', false));

		assertType('array<string, class-string>', class_parents($object, $bool));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, $bool));
		assertType('array<string, class-string>|false', class_parents($objectOrString, $bool));
		assertType('array<string, class-string>|false', class_parents($classString, $bool));
		assertType('array<string, class-string>|false', class_parents($string, $bool));
		assertType('false', class_parents('thisIsNotAClass', $bool));

		assertType('array<string, class-string>', class_parents($object, $mixed));
		assertType('array<string, class-string>|false', class_parents($objectOrClassString, $mixed));
		assertType('array<string, class-string>|false', class_parents($objectOrString, $mixed));
		assertType('array<string, class-string>|false', class_parents($classString, $mixed));
		assertType('array<string, class-string>|false', class_parents($string, $mixed));
		assertType('false', class_parents('thisIsNotAClass', $mixed));
	}

}
