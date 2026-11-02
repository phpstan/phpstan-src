<?php declare(strict_types = 1);

namespace FixBaselineE2E;

function foo(): void
{
	echo $a;
	echo $b;
	echo $c;
}

function bar(int $i): string
{
	return $i;
}

function baz(): void
{
	echo $d;
	echo $e;
}

function qux(): void
{
	echo $f;
	echo $f;
}

function quux(): void
{
	echo $g;
	echo $g;
	echo $g;
	echo $h;
}
