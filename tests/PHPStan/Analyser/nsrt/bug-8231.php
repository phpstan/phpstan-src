<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8231;

use function PHPStan\Testing\assertType;

function foo(string $x): void {}

function test(string|null $x): void {
	if ((string)$x !== '') {
		assertType('non-empty-string', $x);
		foo($x);
	}
}

function testIdentical(string|null $x): void {
	if ((string)$x === '') {
		assertType("''|null", $x);
	} else {
		assertType('non-empty-string', $x);
	}
}

function testInt(int|null $x): void {
	if ((string)$x !== '') {
		assertType('int', $x);
	}
}

function testIntString(int|string|null $x): void {
	if ((string)$x !== '') {
		assertType('int|non-empty-string', $x);
	}
}

function testBool(bool|string|null $x): void {
	if ((string)$x !== '') {
		assertType('non-empty-string|true', $x);
	}
}

// (int) cast narrowing
function testIntCast(int|null $x): void {
	if ((int)$x !== 0) {
		assertType('int<min, -1>|int<1, max>', $x);
	}
}

function testIntCastIdentical(int|null $x): void {
	if ((int)$x === 0) {
		assertType('0|null', $x);
	} else {
		assertType('int<min, -1>|int<1, max>', $x);
	}
}

function testIntCastWithString(int|string|null $x): void {
	if ((int)$x !== 0) {
		assertType("int<min, -1>|int<1, max>|non-falsy-string", $x);
	}
}

function testIntCastWithFloat(float|null $x): void {
	if ((int)$x !== 0) {
		assertType('float', $x);
	}
}

function testIntCastWithBool(bool|int|null $x): void {
	if ((int)$x !== 0) {
		assertType('int<min, -1>|int<1, max>|true', $x);
	}
}
