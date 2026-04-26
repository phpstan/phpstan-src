<?php declare(strict_types = 1);

namespace Bug3842;

use function PHPStan\Testing\assertType;

class ClassA
{
	public static function callback(): void
	{
	}
}

class ClassB
{
	public function callback(): void
	{
	}
}

function testIsArrayOnCallable(callable $value): void {
	if (is_array($value)) {
		assertType('array<mixed, mixed>&callable(): mixed', $value);
		assertType('class-string|object', $value[0]);
		assertType('string', $value[1]);
	}
}

/** @param callable-array $value */
function testCallableArrayPhpDoc(array $value): void {
	assertType('array&callable(): mixed', $value);
	assertType('class-string|object', $value[0]);
	assertType('string', $value[1]);
}

function testIsStringOnCallable(callable $value): void {
	if (is_string($value)) {
		assertType('callable-string', $value);
	}
}

/** @param array{string|object, string} $values */
function check(array $values): void {
}

/** @param array{class-string|object, string} $values */
function checkClassString(array $values): void {
}

/** @param 0|1 $offset */
function testCallableArrayUnionOffset(callable $value, int $offset): void {
	if (is_array($value)) {
		assertType('object|string', $value[$offset]);
	}
}

function testPassCallableArray(callable $value): void {
	if (is_array($value)) {
		check($value);
		checkClassString($value);
	}
}
