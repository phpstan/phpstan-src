<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8231;

use function PHPStan\Testing\assertType;

function foo(string $x): void {}

function testStringCastNotIdenticalEmpty(string|null $x): void {
	if ((string)$x !== '') {
		assertType('non-empty-string', $x);
		foo($x);
	} else {
		assertType("''|null", $x);
	}
	assertType('string|null', $x);
}

function testStringCastIdenticalEmpty(string|null $x): void {
	if ((string)$x === '') {
		assertType("''|null", $x);
	} else {
		assertType('non-empty-string', $x);
	}
}

function testStringCastNotIdenticalEmptyReversed(string|null $x): void {
	if ('' !== (string)$x) {
		assertType('non-empty-string', $x);
	} else {
		assertType("''|null", $x);
	}
}

function testStringCastIntNull(int|null $y): void {
	if ((string)$y !== '') {
		assertType('int', $y);
	} else {
		assertType('null', $y);
	}
}

/** @param string|false $z */
function testStringCastStringFalse(string|false $z): void {
	if ((string)$z !== '') {
		assertType('non-empty-string', $z);
	} else {
		assertType("''|false", $z);
	}
}

/** @param bool|null $b */
function testStringCastBoolNull(bool|null $b): void {
	if ((string)$b !== '') {
		assertType('true', $b);
	} else {
		assertType('false|null', $b);
	}
}

function testStringCastIdenticalNonEmpty(string|null $x): void {
	if ((string)$x === 'hello') {
		assertType('string', $x);
	}
}
