<?php // lint >= 8.0

declare(strict_types = 1);

namespace BenevolentUnionDescription;

function takesNumber(float|int $num): void
{
}

function takesString(string $s): void
{
}

function doFoo(int|float $number, int $scale): void
{
	$coefficient = 10 ** $scale;
	takesNumber((string) ($number * $coefficient));
	takesString($coefficient);
}
