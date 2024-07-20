<?php declare(strict_types = 1);

namespace InvalidIncDecUnion;

/**
 * @param __benevolent<scalar|null|array|object> $benevolentUnion
 * @param string|int|float|bool|null $okUnion
 * @param scalar|null|array|object $union
 * @param __benevolent<array|object> $badBenevolentUnion
 */
function foo($benevolentUnion, $okUnion, $union, $badBenevolentUnion): void
{
	$a = $benevolentUnion;
	$a++;
	$a = $benevolentUnion;
	--$a;

	$a = $okUnion;
	$a++;
	$a = $okUnion;
	--$a;

	$a = $union;
	$a++;
	$a = $union;
	--$a;

	$a = $badBenevolentUnion;
	$a++;
	$a = $badBenevolentUnion;
	--$a;
}
