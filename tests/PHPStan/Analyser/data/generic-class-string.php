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
	}

	if (is_subclass_of($a, 'DateTimeInterface') || is_subclass_of($a, 'stdClass')) {
		assertType('class-string<DateTimeInterface>|class-string<stdClass>|DateTimeInterface|stdClass', $a);
		assertType('DateTimeInterface|stdClass', new $a());
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
	}
}

/**
 * @param object $a
 */
function testObject($a) {
	assertType('object', new $a());

	if (is_subclass_of($a, 'DateTimeInterface')) {
		assertType('DateTimeInterface', $a);
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
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
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
	}

	if (is_subclass_of($a, C::class)) {
		assertType('int', $a::f());
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
