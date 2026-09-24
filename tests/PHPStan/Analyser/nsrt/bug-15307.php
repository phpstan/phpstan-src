<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15307;

use function PHPStan\Testing\assertType;

function f(): int|false
{
	return rand(0, 9999) ?: false;
}

function doFoo(): void
{
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
}

/**
 * @param int|true $x
 */
function doBar(bool $a, $x, int $y, int|false $z, ?int $n, string $s): void
{
	if ((!$a ? f() : 0) !== false) {
		assertType('bool', $a);
	}

	if (($a ? $x : $y) !== true) {
		assertType('bool', $a);
		assertType('int|true', $x);
	}

	if (($a ? $z : 0) !== false) {
		assertType('bool', $a);
	}

	if (($z ?: 0) !== false) {
		assertType('int|false', $z);
	}

	if (($s ?: 0) !== false) {
		assertType('string', $s);
	}

	if (($a ? true : false) !== false) {
		assertType('true', $a);
	}

	if (($a ? 1 : false) !== false) {
		assertType('true', $a);
	}

	if (($s ?: false) !== false) {
		assertType('non-falsy-string', $s);
	}
}

/**
 * @param int|true|null $m
 */
function doCoalesce(?bool $b, ?int $n, $m, ?true $t): void
{
	if (!($b ?? false)) {
		assertType('bool|null', $b);
	}

	if (($b ?? false) === false) {
		assertType('bool|null', $b);
	}

	if (($b ?? false) !== true) {
		assertType('bool|null', $b);
	}

	if (($m ?? false) !== true) {
		assertType('int|true|null', $m);
	}

	if (!($t ?? false)) {
		assertType('null', $t);
	}

	if (($t ?? false) === false) {
		assertType('null', $t);
	}

	if (($t ?? 0) !== true) {
		assertType('null', $t);
	}

	if (($n ?? false) === false) {
		assertType('null', $n);
	}
}
