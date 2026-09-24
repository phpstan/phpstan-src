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

function ternaryNotIdenticalFalse(bool $a): void
{
	if ((!$a ? f() : 0) !== false) {
		assertType('bool', $a);
	} else {
		assertType('bool', $a);
	}

	if (($a ? 0 : false) !== false) {
		assertType('true', $a);
	} else {
		assertType('bool', $a);
	}
}

function ternaryNotIdenticalTrue(bool $a, int|true $x): void
{
	if (($a ? $x : 0) !== true) {
		assertType('bool', $a);
	} else {
		assertType('true', $a);
	}

	if (($a ? 1 : true) !== true) {
		assertType('true', $a);
	}
}

function ternaryIdenticalFalse(int $i, int|bool $x): void
{
	if (($i ? 0 : $x) === false) {
		assertType('int', $i);
	} else {
		assertType('int', $i);
	}
}

function shortTernaryNotIdentical(?int $i, ?int $j): void
{
	if (($i ?: false) !== false) {
		assertType('int<min, -1>|int<1, max>', $i);
	} else {
		assertType('0|null', $i);
	}

	if (($j ?: 0) !== true) {
		assertType('int|null', $j);
	}
}

function coalesceNotIdenticalTrue(?int $i, ?int $j, ?bool $k): void
{
	if (($i ?? 0) !== true) {
		assertType('int|null', $i);
	}

	if (($j ?? true) !== true) {
		assertType('int', $j);
	} else {
		assertType('int|null', $j);
	}

	if (($k ?? 0) !== true) {
		assertType('bool|null', $k);
	} else {
		assertType('true', $k);
	}
}

function coalesceFalsey(?int $i, ?true $j): void
{
	if (!($i ?? 1)) {
		assertType('int', $i);
	} else {
		assertType('int|null', $i);
	}

	if (!($j ?? false)) {
		assertType('null', $j);
	} else {
		assertType('true', $j);
	}
}

function coalesceNotIdenticalFalse(?int $i): void
{
	if (($i ?? false) !== false) {
		assertType('int', $i);
	} else {
		assertType('null', $i);
	}
}
