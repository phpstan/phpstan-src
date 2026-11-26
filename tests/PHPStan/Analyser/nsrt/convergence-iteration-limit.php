<?php // lint >= 8.1

declare(strict_types = 1);

namespace ConvergenceIterationLimit;

use function PHPStan\Testing\assertType;

// each walk moves Side::Right one variable further, so the chains take more
// walks to converge than the iteration limit allows: the variables they write
// then keep no type instead of the one of the last walk

enum Side
{

	case Left;
	case Right;

}

function takes(callable $f): void
{
}

function escapedByRefClosure(): void
{
	$a = $b = $c = $d = $e = $g = $h = $i = $j = $k = $l = $m = Side::Left;
	$f = function () use (&$a, &$b, &$c, &$d, &$e, &$g, &$h, &$i, &$j, &$k, &$l, &$m): void {
		$m = $l; $l = $k; $k = $j; $j = $i; $i = $h; $h = $g; $g = $e; $e = $d; $d = $c; $c = $b; $b = $a; $a = Side::Right;
	};
	takes($f);
	assertType('mixed', $m);
}

function escapedByRefClosureInvoked(): void
{
	$a = $b = $c = $d = $e = $g = $h = $i = $j = $k = $l = $m = Side::Left;
	$f = function () use (&$a, &$b, &$c, &$d, &$e, &$g, &$h, &$i, &$j, &$k, &$l, &$m): void {
		$m = $l; $l = $k; $k = $j; $j = $i; $i = $h; $h = $g; $g = $e; $e = $d; $d = $c; $c = $b; $b = $a; $a = Side::Right;
	};
	takes($f);
	$a = $b = $c = $d = $e = $g = $h = $i = $j = $k = $l = $m = Side::Left;
	$f();
	assertType('mixed', $m);
}

function backwardGoto(bool $again): void
{
	$a = $b = $c = $d = $e = $g = $h = $i = $j = $k = $l = $m = Side::Left;
	start:
	$m = $l; $l = $k; $k = $j; $j = $i; $i = $h; $h = $g; $g = $e; $e = $d; $d = $c; $c = $b; $b = $a; $a = Side::Right;
	if ($again) {
		goto start;
	}
	assertType('mixed', $m);
}

function staticVariables(): void
{
	static $v0 = Side::Left, $v1 = Side::Left, $v2 = Side::Left, $v3 = Side::Left, $v4 = Side::Left, $v5 = Side::Left, $v6 = Side::Left, $v7 = Side::Left, $v8 = Side::Left, $v9 = Side::Left, $v10 = Side::Left, $v11 = Side::Left, $v12 = Side::Left, $v13 = Side::Left, $v14 = Side::Left, $v15 = Side::Left, $v16 = Side::Left, $v17 = Side::Left, $v18 = Side::Left, $v19 = Side::Left, $v20 = Side::Left, $v21 = Side::Left, $v22 = Side::Left, $v23 = Side::Left, $v24 = Side::Left;
	assertType('mixed', $v24);
	$v24 = $v23;
	$v23 = $v22;
	$v22 = $v21;
	$v21 = $v20;
	$v20 = $v19;
	$v19 = $v18;
	$v18 = $v17;
	$v17 = $v16;
	$v16 = $v15;
	$v15 = $v14;
	$v14 = $v13;
	$v13 = $v12;
	$v12 = $v11;
	$v11 = $v10;
	$v10 = $v9;
	$v9 = $v8;
	$v8 = $v7;
	$v7 = $v6;
	$v6 = $v5;
	$v5 = $v4;
	$v4 = $v3;
	$v3 = $v2;
	$v2 = $v1;
	$v1 = $v0;
	$v0 = Side::Right;
}
