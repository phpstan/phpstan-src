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
	// The by-ref $count is passed, so str_replace() is possibly impure.
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
	// The by-ref $matches is passed, so preg_match() is possibly impure.
	return (int) preg_match('/a/', $s, $matches);
}
