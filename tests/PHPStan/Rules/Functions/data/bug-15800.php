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

/**
 * @param array<string, int|float|bool|null|string|\Stringable> $pairs
 */
function ok(array $pairs): string
{
	strtr('abc', 'a', 'b');
	return strtr('abc', $pairs);
}

/**
 * @param array<\stdClass> $objects
 * @param array<string> $strings
 */
function others(array $objects, array $strings): void
{
	substr_replace($objects, 'x', 0);
	substr_replace($strings, $objects, 0);
	substr_replace('abc', 'x', 0);
	substr_replace($strings, $strings, 0);
	preg_grep('/a/', $objects);
	preg_grep('/a/', $strings);
	preg_replace_callback_array(['/a/' => static fn (array $m): string => 'b'], $objects);
	preg_replace_callback_array(['/a/' => static fn (array $m): string => 'b'], $strings);
	setlocale(LC_ALL, $objects);
	setlocale(LC_ALL, $strings);
	setlocale(LC_ALL, 'C');
	proc_open($objects, [], $pipes, null, $objects);
	proc_open($strings, [], $pipes, null, $strings);
	pcntl_exec('/bin/true', $objects, $objects);
	pcntl_exec('/bin/true', $strings, $strings);
	array_diff_uassoc($objects, $objects, static fn ($a, $b): int => 0);
	array_diff_uassoc($strings, $strings, static fn ($a, $b): int => 0);
	array_intersect_uassoc($objects, $strings, static fn ($a, $b): int => 0);
}
