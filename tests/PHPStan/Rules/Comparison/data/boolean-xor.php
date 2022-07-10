<?php

namespace BooleanXor;

true xor true;

function alwaysFalse(): bool
{
	return true xor true;
}

function maybe(bool $left, bool $right): bool
{
	return $left xor $right;
}

$foo = new stdClass();
$bar = new stdClass();

$foo xor $bar;

function alwaysTrue(): bool
{
	return 1 xor 0;
}

/**
 * @param true $left
 * @param true $right
 */
function phpdocAlways(bool $left, bool $right): bool
{
	return $left xor $right;
}
