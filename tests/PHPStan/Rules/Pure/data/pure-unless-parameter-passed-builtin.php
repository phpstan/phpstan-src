<?php declare(strict_types = 1);

namespace PureUnlessParameterPassedBuiltin;

/**
 * @phpstan-pure
 */
function pureStrReplaceWithoutCount(string $s): string
{
	// The by-ref $count is omitted, so str_replace() is pure.
	return str_replace('a', 'b', $s);
}

/**
 * @phpstan-pure
 */
function pureStrReplaceWithCount(string $s): string
{
	// The by-ref $count is passed, so str_replace() is impure (the flag is certain).
	$count = 0;

	return str_replace('a', 'b', $s, $count);
}

/**
 * @phpstan-pure
 */
function purePregMatchWithoutMatches(string $s): int
{
	// The by-ref $matches is omitted, so preg_match() is pure.
	return (int) preg_match('/a/', $s);
}

/**
 * @phpstan-pure
 */
function purePregMatchWithMatches(string $s): int
{
	// The by-ref $matches is passed, so preg_match() is impure (the flag is certain).
	return (int) preg_match('/a/', $s, $matches);
}

/**
 * @phpstan-pure
 */
function purePregFilterWithoutCount(string $s): ?string
{
	// The by-ref $count is omitted, so preg_filter() is pure.
	return preg_filter('/a/', 'b', $s);
}

/**
 * @phpstan-pure
 */
function purePregFilterWithCount(string $s): ?string
{
	// The by-ref $count is passed, so preg_filter() is impure (the flag is certain).
	$count = 0;

	return preg_filter('/a/', 'b', $s, -1, $count);
}

/**
 * @phpstan-pure
 */
function purePregReplaceCallbackPureCallback(string $s): ?string
{
	// preg_replace_callback() carries both flags: the callback is pure and the by-ref
	// $count is omitted, so the call stays pure.
	return preg_replace_callback('/a/', static fn (array $m): string => $m[0], $s);
}

/**
 * @phpstan-pure
 */
function purePregReplaceCallbackWithCount(string $s): ?string
{
	$count = 0;
	// The callback is pure but the by-ref $count is passed, so the call is impure.
	return preg_replace_callback('/a/', static fn (array $m): string => $m[0], $s, -1, $count);
}

/**
 * @phpstan-pure
 */
function purePregReplaceCallbackImpureCallback(string $s): ?string
{
	// The callback itself is impure, so the call is impure regardless of $count.
	return preg_replace_callback('/a/', static function (array $m): string {
		echo $m[0];

		return $m[0];
	}, $s);
}
