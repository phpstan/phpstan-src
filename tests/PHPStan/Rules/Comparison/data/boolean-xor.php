<?php

namespace BooleanXor;

function leftAlwaysTrue(bool $right): bool
{
	return true xor $right;
}

function rightAlwaysTrue(bool $left): bool
{
	return $left xor true;
}

function leftAlwaysFalse(bool $right): bool
{
	return false xor $right;
}

function rightAlwaysFalse(bool $left): bool
{
	return $left xor false;
}

/**
 * @param true $left
 * @param true $right
 */
function phpdocAlways(bool $left, bool $right): bool
{
	return $left xor $right;
}

function skip(\DateTimeImmutable $date): void
{
	$date instanceof \stdClass xor $date instanceof \DateTimeImmutable;
}
