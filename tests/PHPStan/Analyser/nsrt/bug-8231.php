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
