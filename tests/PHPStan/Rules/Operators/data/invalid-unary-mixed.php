<?php declare(strict_types = 1); // lint >= 8.0

namespace InvalidUnaryMixed;

/**
 * @template T
 * @param T $a
 */
function genericMixed(mixed $a): void
{
	var_dump(+$a);
	var_dump(-$a);
	var_dump(~$a);
}

function explicitMixed(mixed $a): void
{
	var_dump(+$a);
	var_dump(-$a);
	var_dump(~$a);
}

function implicitMixed($a): void
{
	var_dump(+$a);
	var_dump(-$a);
	var_dump(~$a);
}
