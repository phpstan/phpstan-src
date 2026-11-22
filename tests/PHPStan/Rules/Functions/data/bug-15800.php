<?php declare(strict_types=1);

namespace Bug15800;

/** @param array<string, \stdClass> $pairs */
function a(array $pairs): string
{
	return strtr('abc', $pairs);
}

/** @param array<string, array<int>> $pairs */
function b(array $pairs): string
{
	return strtr('abc', $pairs);
}

/** @param array<int, \stdClass> $parts */
function c(array $parts): string
{
	return implode(',', $parts);
}

/** @param array<string, string|int|float|bool|null> $pairs */
function d(array $pairs): string
{
	return strtr('abc', $pairs);
}
