<?php

namespace MissingPureTagFix;

function add(int $a, int $b): int
{
	return $a + $b;
}

/**
 * Adds one.
 */
function addOne(int $a): int
{
	return $a + 1;
}

/** @param positive-int $a */
function singleLine(int $a): int
{
	return $a;
}

/** */
function emptyPhpDoc(): int
{
	return 1;
}
