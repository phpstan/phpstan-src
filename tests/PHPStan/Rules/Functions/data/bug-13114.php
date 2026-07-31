<?php declare(strict_types = 1);

namespace Bug13114;

class C {
	static function f(): void {}
	function g(): void {}
}

/**
 * @param callable&array{class-string|object, string} $arg
 */
function foo(array $arg): void {}

/**
 * @param callable&array<mixed> $arg
 */
function bar(array $arg): void {}

/**
 * @param callable-array $arg
 */
function baz($arg): void {}

/**
 * @param callable&string $arg
 */
function callableString($arg): void {}

/**
 * @param callable&object $arg
 */
function callableObject($arg): void {}

foo([new C, 'f']);
foo([new C, 'g']);
foo([new C, 'h']); // error
foo([C::class, 'f']);

bar([new C, 'f']);
bar([new C, 'g']);
bar([new C, 'h']); // error
bar([C::class, 'f']);

baz([new C, 'f']);
baz([new C, 'g']);
baz([new C, 'h']); // error
baz([1, 2]); // error
baz(42); // error

callableString('strtoupper');
callableString('nonexistentFunction'); // error
callableString(42); // error

callableObject(new C()); // error
callableObject(function (): void {});
callableObject(42); // error

function takesInt(int $i): void {}

/**
 * @param array<mixed> $a
 */
function narrowedByIsCallable(array $a): void
{
	if (is_callable($a)) {
		takesInt($a); // error
	}
}

/**
 * @param mixed $m
 */
function narrowedFromMixed($m): void
{
	if (is_callable($m) && is_array($m)) {
		takesInt($m); // error
	}
}
