<?php declare(strict_types = 1);

namespace Bug15228ParamOut;

/**
 * @param-out int|null $p
 */
function coalesceInvalid(?int &$p): void
{
	$p ??= 'boom';
}

/**
 * @param-out list<int>|null $p
 * @param list<int>|null $p
 * @param list<string> $s
 */
function coalesceValid(?array &$p, array $s): void
{
	$p = $s;
	$p ??= [1, 2];
}
