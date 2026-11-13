<?php

namespace Bug15729;

/** @param array<string, bool> $map */
function foo(?string $key, array $map): bool
{
	return isset($key, $map[$key]);
}

/** @param array<string, bool> $map */
function keyCheckedAfterOffset(?string $key, array $map): bool
{
	return isset($map[$key], $key);
}

/** @param array<string, array<string, bool>> $map */
function nested(?string $key, ?string $innerKey, array $map): bool
{
	return isset($key, $innerKey, $map[$key][$innerKey]);
}
