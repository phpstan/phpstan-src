<?php declare(strict_types = 1);

namespace InvalidBinaryOperatorWithMixed;

/**
 * @template T
 * @param T $a
 */
function genericMixed(mixed $a): void
{
	var_dump($a . 'a');
	$b = 'a';
	$b .= $a;
	$bool = rand() > 0;
	var_dump($a ** 2);
	var_dump($a * 2);
	var_dump($a / 2);
	var_dump($a % 2);
	var_dump($a + 2);
	var_dump($a - 2);
	var_dump($a << 2);
	var_dump($a >> 2);
	var_dump($a & 2);
	var_dump($a | 2);
	$c = 5;
	$c += $a;

	$c = 5;
	$c -= $a;

	$c = 5;
	$c *= $a;

	$c = 5;
	$c **= $a;

	$c = 5;
	$c /= $a;

	$c = 5;
	$c %= $a;

	$c = 5;
	$c &= $a;

	$c = 5;
	$c |= $a;

	$c = 5;
	$c ^= $a;

	$c = 5;
	$c <<= $a;

	$c = 5;
	$c >>= $a;
}

function explicitMixed(mixed $a): void
{
	var_dump($a . 'a');
	$b = 'a';
	$b .= $a;
	$bool = rand() > 0;
	var_dump($a ** 2);
	var_dump($a * 2);
	var_dump($a / 2);
	var_dump($a % 2);
	var_dump($a + 2);
	var_dump($a - 2);
	var_dump($a << 2);
	var_dump($a >> 2);
	var_dump($a & 2);
	var_dump($a | 2);
	$c = 5;
	$c += $a;

	$c = 5;
	$c -= $a;

	$c = 5;
	$c *= $a;

	$c = 5;
	$c **= $a;

	$c = 5;
	$c /= $a;

	$c = 5;
	$c %= $a;

	$c = 5;
	$c &= $a;

	$c = 5;
	$c |= $a;

	$c = 5;
	$c ^= $a;

	$c = 5;
	$c <<= $a;

	$c = 5;
	$c >>= $a;
}

function implicitMixed($a): void
{
	var_dump($a . 'a');
	$b = 'a';
	$b .= $a;
	$bool = rand() > 0;
	var_dump($a ** 2);
	var_dump($a * 2);
	var_dump($a / 2);
	var_dump($a % 2);
	var_dump($a + 2);
	var_dump($a - 2);
	var_dump($a << 2);
	var_dump($a >> 2);
	var_dump($a & 2);
	var_dump($a | 2);
	$c = 5;
	$c += $a;

	$c = 5;
	$c -= $a;

	$c = 5;
	$c *= $a;

	$c = 5;
	$c **= $a;

	$c = 5;
	$c /= $a;

	$c = 5;
	$c %= $a;

	$c = 5;
	$c &= $a;

	$c = 5;
	$c |= $a;

	$c = 5;
	$c ^= $a;

	$c = 5;
	$c <<= $a;

	$c = 5;
	$c >>= $a;
}
