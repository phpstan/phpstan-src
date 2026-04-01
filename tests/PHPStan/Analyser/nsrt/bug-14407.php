<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14407;

use function PHPStan\Testing\assertType;

enum SomeEnum {
	case A;
	case B;
}

function () {
	$arr = [];

	if (rand(0, 1) === 0) {
		$arr[] = SomeEnum::A;
	} else {
		$arr[] = SomeEnum::B;
	}

	if (rand(0, 1) === 0) {
		$x = SomeEnum::A;
	} else {
		$x = SomeEnum::B;
	}

	if (!in_array($x, $arr)) {
		// either $x=A, $arr=[B] or $x=B, $arr=[A]
		assertType('Bug14407\SomeEnum::A|Bug14407\SomeEnum::B', $x);
	}

	if (!in_array($x, $arr) && $x === SomeEnum::A) {
		// $x=A, $arr=[B]
		assertType('Bug14407\SomeEnum::A', $x);
	}
};

function () {
	$arr = [SomeEnum::A, SomeEnum::B];

	if (rand(0, 1) === 0) {
		$x = SomeEnum::A;
	} else {
		$x = SomeEnum::B;
	}

	if (!in_array($x, $arr)) {
		// array always contains both A and B, so this is correctly *NEVER*
		assertType('*NEVER*', $x);
	}
};

function () {
	$arr = [];

	$r = rand(0, 2);

	if ($r === 0) {
		$arr[] = SomeEnum::A;
	} elseif ($r === 1) {
		$arr[] = SomeEnum::B;
	}

	if (rand(0, 1) === 0) {
		$x = SomeEnum::A;
	} else {
		$x = SomeEnum::B;
	}

	// arr might be empty, so no narrowing possible
	if (!in_array($x, $arr) && $x === SomeEnum::A) {
		assertType('Bug14407\SomeEnum::A', $x);
	}
};

/**
 * @param 'a'|'b'|'c' $x
 * @param array{a: 'a', c: 'c'}|array{a?:'a', b: 'b'} $a
 */
function testUnionWithOptionalKeys($x, $a): void
{
	assertType("array{a: 'a', c: 'c'}|array{a?: 'a', b: 'b'}", $a);
	if (!\in_array($x, $a, true)) {
		assertType("'a'|'b'|'c'", $x);
	}
};

/**
 * @param 'a'|'b'|'c' $x
 * @param non-empty-array<'a'|'b'> $a
 */
function testNonConstantArray($x, $a): void
{
	assertType("non-empty-array<'a'|'b'>", $a);
	if (!\in_array($x, $a, true)) {
		assertType("'a'|'b'|'c'", $x);
	}
};
