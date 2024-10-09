<?php

namespace Bug11301;

/**
 * @return array<int, string>
 */
function cInt(): array
{
	$a = ['12345'];
	$b = ['abc'];

	return array_combine($a, $b);
}

/**
 * @return array<int, string>
 */
function cInt2(): array
{
	$a = ['12345', 123];
	$b = ['abc', 'def'];

	return array_combine($a, $b);
}

/**
 * @return array<string, string>
 */
function cString(): array
{
	$a = ['12345'];
	$b = ['abc'];

	return array_combine($a, $b);
}


/**
 * @return array<int|string, string>
 */
function cString2(): array
{
	$a = ['12345', 123, 'a'];
	$b = ['abc', 'def', 'xy'];

	return array_combine($a, $b);
}
