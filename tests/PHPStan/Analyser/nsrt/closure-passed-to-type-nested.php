<?php declare(strict_types = 1);

namespace ClosurePassedToTypeNested;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

/** @param array<string, \Closure(int): void> $callbacks */
function acceptCallbacks(array $callbacks): void {}

acceptCallbacks([
	'foo' => function ($value): void {
		assertType('int', $value);
		assertNativeType('mixed', $value);
	},
	'bar' => fn ($value) => assertType('int', $value),
]);

/** @param array{first: \Closure(int): void, second: \Closure(string): void, \Closure(float): void} $callbacks */
function acceptShape(array $callbacks): void {}

acceptShape([
	'first' => function ($value): void {
		assertType('int', $value);
	},
	'second' => function ($value): void {
		assertType('string', $value);
	},
	function ($value): void {
		assertType('float', $value);
	},
]);

/** @param list<\Closure(int): void> $callbacks */
function acceptList(array $callbacks): void {}

acceptList([
	function ($value): void {
		assertType('int', $value);
	},
	function ($value): void {
		assertType('int', $value);
	},
]);

/** @param array<string, array<string, \Closure(bool): void>> $callbacks */
function acceptNested(array $callbacks): void {}

acceptNested([
	'a' => [
		'b' => function ($value): void {
			assertType('bool', $value);
		},
	],
]);

/** @param \Closure(int): void|array<string, \Closure(string): void> $callbacks */
function acceptUnion($callbacks): void {}

acceptUnion(function ($value): void {
	assertType('int', $value);
});

acceptUnion([
	'foo' => function ($value): void {
		assertType('string', $value);
	},
]);

/**
 * @template T
 * @param list<T> $items
 * @param array<string, \Closure(T): void> $callbacks
 */
function acceptGeneric(array $items, array $callbacks): void {}

acceptGeneric([1, 2, 3], [
	'foo' => function ($value): void {
		assertType('1|2|3', $value);
	},
]);

/** @param array<string, \Closure(int): void> $callbacks */
function acceptTernary(array $callbacks): void {}

acceptTernary(rand(0, 1) === 0 ? [
	'foo' => function ($value): void {
		assertType('int', $value);
	},
] : [
	'bar' => function ($value): void {
		assertType('int', $value);
	},
]);

/** @param array<string, \Closure(int): void> $callbacks */
function acceptReturnType(array $callbacks): void {}

acceptReturnType([
	'foo' => function ($value) {
		return $value + 1;
	},
]);

function (array $callbacks): void {
	acceptCallbacks([
		'foo' => function (string $value): void {
			assertType('string', $value);
		},
	]);
};

/**
 * @template T
 * @param array<T, \Closure(T): void> $callbacks
 */
function acceptKeyedGeneric(array $callbacks): void {}

acceptKeyedGeneric([
	'foo' => function ($value): void {
		assertType("'bar'|'foo'", $value);
	},
	'bar' => fn ($value) => assertType("'bar'|'foo'", $value),
]);

/**
 * @template T
 * @param array<int, T> $values
 * @param array<int, \Closure(T): void> $callbacks
 */
function acceptGenericTernaryItem(array $values, array $callbacks): void {}

acceptGenericTernaryItem([1, 2], [
	rand(0, 1) === 0 ? function ($value): void {
		assertType('1|2', $value);
	} : function ($value): void {
		assertType('1|2', $value);
	},
]);

/**
 * @template T
 * @param array<string, array{T, \Closure(T): void}> $callbacks
 */
function acceptNestedGeneric(array $callbacks): void {}

acceptNestedGeneric([
	'a' => ['x', function ($value): void {
		assertType("'x'", $value);
	}],
]);
