<?php

namespace PHPStan\Generics\GenericClassStringType;

use function PHPStan\Testing\assertType;

class C
{
	public static function f(): int {
		return 0;
	}
}

/**
 * @param mixed $a
 */
function testMixed($a) {
	assertType('object', new $a());

	if (is_subclass_of($a, 'DateTimeInterface')) {
		assertType('class-string<DateTimeInterface>|DateTimeInterface', $a);
		assertType('DateTimeInterface', new $a());
	} else {
		assertType('mixed', $a);
	}

	if (is_subclass_of($a, 'DateTimeInterface') || is_subclass_of($a, 'stdClass')) {
		assertType('class-string<DateTimeInterface>|class-string<stdClass>|DateTimeInterface|stdClass', $a);
		assertType('DateTimeInterface|stdClass', new $a());
	} else {
		assertType('mixed', $a);
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
	} else {
		assertType('mixed', $a);
	}
}

/**
 * @param object $a
 */
function testObject($a) {
	assertType('object', new $a());

	if (is_subclass_of($a, 'DateTimeInterface')) {
		assertType('DateTimeInterface', $a);
	} else {
		assertType('object', $a);
	}
}

/**
 * @param string $a
 */
function testString($a) {
	assertType('object', new $a());

	if (is_subclass_of($a, 'DateTimeInterface')) {
		assertType('class-string<DateTimeInterface>', $a);
		assertType('DateTimeInterface', new $a());
	} else {
		assertType('string', $a);
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
	} else {
		assertType('string', $a);
	}
}

/**
 * @param string|object $a
 */
function testStringObject($a) {
	assertType('object', new $a());

	if (is_subclass_of($a, 'DateTimeInterface')) {
		assertType('class-string<DateTimeInterface>|DateTimeInterface', $a);
		assertType('DateTimeInterface', new $a());
	} else {
		assertType('object|string', $a);
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
	} else {
		assertType('object|string', $a);
	}
}

/**
 * @param class-string<\DateTimeInterface> $a
 */
function testClassString($a) {
	assertType('DateTimeInterface', new $a());

	if (is_subclass_of($a, 'DateTime')) {
		assertType('class-string<DateTime>', $a);
		assertType('DateTime', new $a());
	} else {
		assertType('class-string<DateTimeInterface>', $a);
	}
}

/**
 * @param object|string $a
 * @param class-string<\DateTimeInterface> $b
 */
function testClassStringAsClassName($a, string $b) {
	assertType('object', new $a());

	if (is_subclass_of($a, $b)) {
		assertType('class-string<DateTimeInterface>|DateTimeInterface', $a);
		assertType('DateTimeInterface', new $a());
	} else {
		assertType('object|string', $a);
	}

	if (is_subclass_of($a, $b, false)) {
		assertType('DateTimeInterface', $a);
	} else {
		assertType('object|string', $a);
	}
}

function testClassExists(string $str)
{
	assertType('string', $str);
	if (class_exists($str)) {
		assertType('class-string', $str);
		assertType('object', new $str());
	}

	$existentClass = \stdClass::class;
	if (class_exists($existentClass)) {
		assertType('\'stdClass\'', $existentClass);
	}

	$nonexistentClass = 'NonexistentClass';
	if (class_exists($nonexistentClass)) {
		assertType('\'NonexistentClass\'', $nonexistentClass);
	}
}

function testInterfaceExists(string $str)
{
	assertType('string', $str);
	if (interface_exists($str)) {
		assertType('class-string', $str);
	}
}

function testTraitExists(string $str)
{
	assertType('string', $str);
	if (trait_exists($str)) {
		assertType('class-string', $str);
	}
}
