<?php

namespace Bug5317;

/**
 * @param non-empty-string $in
 */
function nonEmptyString(string $in): void
{
	var_dump(!$in);
}

/**
 * @param non-falsy-string $in
 */
function nonFalsyString(string $in): void
{
	var_dump(!$in);
}
