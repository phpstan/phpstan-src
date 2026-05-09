<?php // lint >= 8.4

declare(strict_types = 1);

namespace ArrayAllTypeNarrowing;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-assert-if-true int $val
 */
function isInt(mixed $val): bool
{
	return is_int($val);
}

/**
 * @phpstan-assert-if-true string $val
 */
function isString(mixed $val): bool
{
	return is_string($val);
}

// array_all with arrow function
/** @param array<mixed> $array */
function testArrayAllArrowFunction(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('array<int>', $array);
	}
	assertType('array<mixed>', $array);
}

// array_all with closure
/** @param array<mixed> $array */
function testArrayAllClosure(array $array): void
{
	if (array_all($array, function ($v) { return is_int($v); })) {
		assertType('array<int>', $array);
	}
}

// array_all with first-class callable (built-in)
/** @param array<mixed> $array */
function testArrayAllFirstClassCallable(array $array): void
{
	if (array_all($array, is_int(...))) {
		assertType('array<int>', $array);
	}
}

// array_all with first-class callable (phpstan-assert-if-true)
/** @param array<mixed> $array */
function testArrayAllAssertIfTrue(array $array): void
{
	if (array_all($array, isInt(...))) {
		assertType('array<int>', $array);
	}
}

// array_all with string callable
/** @param array<mixed> $array */
function testArrayAllStringCallable(array $array): void
{
	if (array_all($array, 'is_int')) {
		assertType('array<int>', $array);
	}
}

// array_all narrowing to string
/** @param array<mixed> $array */
function testArrayAllIsString(array $array): void
{
	if (array_all($array, fn ($v) => is_string($v))) {
		assertType('array<string>', $array);
	}
}

// array_all with instanceof
/** @param array<mixed> $array */
function testArrayAllInstanceof(array $array): void
{
	if (array_all($array, fn ($v) => $v instanceof \stdClass)) {
		assertType('array<stdClass>', $array);
	}
}

// array_all key narrowing
/** @param array<int|string, mixed> $array */
function testArrayAllKeyNarrowing(array $array): void
{
	if (array_all($array, fn ($v, $k) => is_string($k))) {
		assertType('array<string, mixed>', $array);
	}
}

// array_all narrowing both key and value
/** @param array<int|string, int|string> $array */
function testArrayAllBothNarrowing(array $array): void
{
	if (array_all($array, fn ($v, $k) => is_string($k) && is_int($v))) {
		assertType('array<string, int>', $array);
	}
}

// array_all preserves key type when only value is narrowed
/** @param array<string, int|string> $array */
function testArrayAllPreservesKeyType(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('array<string, int>', $array);
	}
}

// array_any in falsey context
/** @param array<int|string> $array */
function testArrayAnyFalsey(array $array): void
{
	if (!array_any($array, fn ($v) => is_int($v))) {
		assertType('array<string>', $array);
	}
	assertType('array<int|string>', $array);
}

// array_any falsey with first-class callable
/** @param array<int|string> $array */
function testArrayAnyFalseyFirstClass(array $array): void
{
	if (!array_any($array, is_int(...))) {
		assertType('array<string>', $array);
	}
}

// array_any falsey with string callable
/** @param array<int|string> $array */
function testArrayAnyFalseyStringCallable(array $array): void
{
	if (!array_any($array, 'is_int')) {
		assertType('array<string>', $array);
	}
}

// array_all with non-empty-array preserves non-empty
/** @param non-empty-array<mixed> $array */
function testArrayAllNonEmptyArray(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('non-empty-array<int>', $array);
	}
}

// assert(array_all(...)) narrows the array
/** @param array<int|string, mixed> $values */
function testAssertArrayAll(array $values): void
{
	assert(array_all($values, fn ($v, $k) => is_string($k)));
	assertType('array<string, mixed>', $values);
}

// array_all with list
/** @param list<int|string> $array */
function testArrayAllList(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('list<int>', $array);
	}
}

// array_all falsey does not narrow (we only know at least one doesn't match)
/** @param array<int|string> $array */
function testArrayAllFalsey(array $array): void
{
	if (!array_all($array, fn ($v) => is_int($v))) {
		assertType('array<int|string>', $array);
	}
}

// array_any truthy does not narrow value type (only one matches)
/** @param array<int|string> $array */
function testArrayAnyTruthy(array $array): void
{
	if (array_any($array, fn ($v) => is_int($v))) {
		assertType('array<int|string>', $array);
	}
}

// array_all with constant array
/** @param array{a: int|string, b: int|string} $array */
function testArrayAllConstantArray(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('array{a: int, b: int}', $array);
	}
}

// array_all with constant array with optional key
/** @param array{a: int|string, b?: int|string} $array */
function testArrayAllConstantArrayOptional(array $array): void
{
	if (array_all($array, fn ($v) => is_int($v))) {
		assertType('array{a: int, b?: int}', $array);
	}
}
