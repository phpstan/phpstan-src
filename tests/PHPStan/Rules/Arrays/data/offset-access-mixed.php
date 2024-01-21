<?php declare(strict_types=1); // lint >= 8.0

namespace OffsetAccessMixed;

/**
 * @template T
 * @param T $a
 */
function foo(mixed $a): void
{
	var_dump($a[5]);
}

function foo2(mixed $a): void
{
	var_dump($a[5]);
}

function foo3($a): void
{
	var_dump($a[5]);
}
