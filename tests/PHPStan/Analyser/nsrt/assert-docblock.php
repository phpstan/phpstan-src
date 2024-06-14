<?php

namespace AssertDocblock;

use function PHPStan\Testing\assertType;

/**
 * @param mixed[] $arr
 * @phpstan-assert string[] $arr
 */
function validateStringArray(array $arr) : void {}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 */
function validateStringArrayIfTrue(array $arr) : bool {
	return true;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-false string[] $arr
 */
function validateStringArrayIfFalse(array $arr) : bool {
	return false;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 * @phpstan-assert-if-false int[] $arr
 */
function validateStringOrIntArray(array $arr) : bool {
	return false;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true =string[] $arr
 * @phpstan-assert-if-false =int[] $arr
 * @phpstan-assert-if-false =non-empty-array $arr
 */
function validateStringOrNonEmptyIntArray(array $arr) : bool {
	return false;
}

/**
 * @param mixed $value
 * @phpstan-assert !null $value
 */
function validateNotNull($value) : void {}


/**
 * @param mixed[] $arr
 */
function takesArray(array $arr) : void {
	assertType('array', $arr);

	validateStringArray($arr);
	assertType('array<string>', $arr);
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfTrue(array $arr) : void {
	assertType('array', $arr);

	if (validateStringArrayIfTrue($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array', $arr);
	}
}
/**
 * @param mixed[] $arr
 */
function takesArrayIfTrue1(array $arr) : void {
	assertType('array', $arr);

	if (!validateStringArrayIfTrue($arr)) {
		assertType('array', $arr);
	} else {
		assertType('array<string>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfFalse(array $arr) : void {
	assertType('array', $arr);

	if (!validateStringArrayIfFalse($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfFalse1(array $arr) : void {
	assertType('array', $arr);

	if (validateStringArrayIfFalse($arr)) {
		assertType('array', $arr);
	} else {
		assertType('array<string>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesStringOrIntArray(array $arr) : void {
	assertType('array', $arr);

	if (validateStringOrIntArray($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array<int>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesStringOrNonEmptyIntArray(array $arr) : void {
	assertType('array', $arr);

	if (validateStringOrNonEmptyIntArray($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('non-empty-array<int>', $arr);
	}
}

/**
 * @param mixed $value
 */
function takesNotNull($value) : void {
	assertType('mixed', $value);

	validateNotNull($value);
	assertType('mixed~null', $value);
}


/**
 * @template T of object
 * @param object $object
 * @param class-string<T> $class
 * @phpstan-assert T $object
 */
function validateClassType(object $object, string $class): void {}

class ClassToValidate {}

function (object $object) {
	validateClassType($object, ClassToValidate::class);
	assertType('AssertDocblock\ClassToValidate', $object);
};


class A {
	/**
	 * @phpstan-assert-if-true int $x
	 */
	public function testInt(mixed $x): bool
	{
		return is_int($x);
	}

	/**
	 * @phpstan-assert-if-true !int $x
	 */
	public function testNotInt(mixed $x): bool
	{
		return !is_int($x);
	}
}

class B extends A
{
	public function testInt(mixed $y): bool
	{
		return parent::testInt($y);
	}
}

function (A $a, $i) {
	if ($a->testInt($i)) {
		assertType('int', $i);
	} else {
		assertType('mixed~int', $i);
	}

	if ($a->testNotInt($i)) {
		assertType('mixed~int', $i);
	} else {
		assertType('int', $i);
	}
};

function (B $b, $i) {
	if ($b->testInt($i)) {
		assertType('int', $i);
	} else {
		assertType('mixed~int', $i);
	}
};

function (A $a, string $i) {
	if ($a->testInt($i)) {
		assertType('*NEVER*', $i);
	} else {
		assertType('string', $i);
	}

	if ($a->testNotInt($i)) {
		assertType('string', $i);
	} else {
		assertType('*NEVER*', $i);
	}
};

function (A $a, int $i) {
	if ($a->testInt($i)) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}

	if ($a->testNotInt($i)) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}
};
