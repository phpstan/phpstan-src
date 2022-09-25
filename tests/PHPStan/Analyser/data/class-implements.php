<?php

namespace ClassImplements;

use DateTime;
use Generator;
use stdClass;
use function PHPStan\Testing\assertType;

class ClassImplements
{

	public function withObject(): void
	{
		assertType("array{DateTimeInterface: 'DateTimeInterface'}", class_implements(new DateTime()));
		assertType("array{Iterator: 'Iterator', Traversable: 'Traversable'}", class_implements(new Generator()));
		assertType('array{}', class_implements(new stdClass()));
		assertType('false', class_implements(new invalidClass()));
	}

	public function withObjectAndAutoloadingDisabled(): void
	{
		assertType("array{DateTimeInterface: 'DateTimeInterface'}", class_implements(new DateTime(), false));
		assertType("array{Iterator: 'Iterator', Traversable: 'Traversable'}", class_implements(new Generator(), false));
		assertType('array{}', class_implements(new stdClass(), false));
		assertType('false', class_implements(new invalidClass(), false));
	}

	/** @param class-string $classString */
	public function withClassName(string $classString, string $className): void
	{
		assertType("array{DateTimeInterface: 'DateTimeInterface'}", class_implements(DateTime::class));
		assertType("array{Iterator: 'Iterator', Traversable: 'Traversable'}", class_implements(Generator::class));
		assertType('array{}', class_implements(stdClass::class));
		assertType('false', class_implements(invalidClass::class));
		assertType('array<string, string>', class_implements($classString));
		assertType('array<string, string>|false', class_implements($className));
	}

	/** @param class-string $classString */
	public function withClassNameAndAutoloadingDisabled(string $classString, string $className): void
	{
		assertType('array<string, string>|false', class_implements(DateTime::class, false));
		assertType('array<string, string>|false', class_implements(stdClass::class, false));
		assertType('array<string, string>|false', class_implements(invalidClass::class, false));
		assertType('array<string, string>|false', class_implements($classString, false));
		assertType('array<string, string>|false', class_implements($className, false));
	}

}
