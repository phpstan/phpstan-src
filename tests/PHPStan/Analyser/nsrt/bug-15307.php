<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15307;

use function PHPStan\Testing\assertType;

function f(): int|false
{
	return rand(0, 9999) ?: false;
}

$a = false;

if (rand(0,1) == 1) {
	$a = true;
}

$b = !$a ? f() : 0;

if ($b === false) {
	// this check erronously changes the type of a to false;
	// it *is* false, but only inside of this branch.
} elseif ($a || $b > 99) {
	// if execution ends up here, $a might very well stil lbe true
	assertType('bool', $a);
}

function exactContexts(bool $a, int|bool $x): void
{
	if ((!$a ? f() : 0) !== false) {
		assertType('bool', $a);
	} else {
		assertType('false', $a);
	}

	if ((!$a ? f() : 0) === false) {
		assertType('false', $a);
	} else {
		assertType('bool', $a);
	}

	if (($a ? $x : true) === true) {
		assertType('bool', $a);
		assertType('bool|int', $x);
	} else {
		assertType('true', $a);
		assertType('int|false', $x);
	}

	if (($a ? $x : 1) !== true) {
		assertType('bool', $a);
		assertType('bool|int', $x);
	} else {
		assertType('true', $a);
		assertType('true', $x);
	}
}
