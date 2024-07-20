<?php declare(strict_types = 1);

namespace InvalidIncDecMixed;

/**
 * @template T
 * @param T $a
 */
function genericMixed(mixed $a): void
{
	$b = $a;
	var_dump(++$b);
	$b = $a;
	var_dump($b++);
	$b = $a;
	var_dump(--$b);
	$b = $a;
	var_dump($b--);
}

function explicitMixed(mixed $a): void
{
	$b = $a;
	var_dump(++$b);
	$b = $a;
	var_dump($b++);
	$b = $a;
	var_dump(--$b);
	$b = $a;
	var_dump($b--);
}

function implicitMixed($a): void
{
	$b = $a;
	var_dump(++$b);
	$b = $a;
	var_dump($b++);
	$b = $a;
	var_dump(--$b);
	$b = $a;
	var_dump($b--);
}
