<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8231Analogous;

use function PHPStan\Testing\assertType;

// Analogous case: (int) cast in strict comparison
function testIntCastNotIdenticalZero(int|null $x): void {
	if ((int)$x !== 0) {
		assertType('int', $x);
	} else {
		assertType('int|null', $x);
	}
}

function testIntCastIdenticalZero(int|null $x): void {
	if ((int)$x === 0) {
		assertType('int|null', $x);
	} else {
		assertType('int', $x);
	}
}

// Analogous case: (float) cast in strict comparison
function testFloatCastNotIdenticalZero(float|null $x): void {
	if ((float)$x !== 0.0) {
		assertType('float', $x);
	} else {
		assertType('float|null', $x);
	}
}

// Analogous case: loose comparison with string cast
function testStringCastLooseNotEqual(string|null $x): void {
	if ((string)$x != '') {
		assertType('non-empty-string', $x);
	} else {
		assertType("''|null", $x);
	}
}

// (bool) cast already works via existing specifyTypesForConstantBinaryExpression
function testBoolCastIdenticalTrue(string|null $x): void {
	if ((bool)$x === true) {
		assertType('non-falsy-string', $x);
	}
}

// Analogous: (int) cast with non-zero constant
function testIntCastIdenticalNonZero(int|null $x): void {
	if ((int)$x === 5) {
		assertType('int', $x);
	}
}

// Analogous: (float) cast with non-zero constant
function testFloatCastIdenticalNonZero(float|null $x): void {
	if ((float)$x === 3.14) {
		assertType('float', $x);
	}
}

// Reversed ordering: 0 !== (int)$x
function testIntCastReversed(int|null $x): void {
	if (0 !== (int)$x) {
		assertType('int', $x);
	}
}
