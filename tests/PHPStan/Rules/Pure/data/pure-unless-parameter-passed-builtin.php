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
	// preg_match() is left out of the metadata even with $matches omitted: an invalid
	// pattern raises a warning and sets preg_last_error(), so the call stays possibly impure.
	return (int) preg_match('/a/', $s);
}

/**
 * @phpstan-pure
 */
function pureStrIreplaceWithoutCount(string $s): string
{
	// The by-ref $count is omitted, so str_ireplace() is pure.
	return str_ireplace('a', 'b', $s);
}

/**
 * @phpstan-pure
 */
function pureStrIreplaceWithCount(string $s): string
{
	// The by-ref $count is passed, so str_ireplace() is impure (the flag is certain).
	$count = 0;

	return str_ireplace('a', 'b', $s, $count);
}

/**
 * @phpstan-pure
 */
function pureSimilarTextWithoutPercent(string $a, string $b): int
{
	// The by-ref $percent is omitted, so similar_text() is pure.
	return similar_text($a, $b);
}

/**
 * @phpstan-pure
 */
function pureSimilarTextWithPercent(string $a, string $b): int
{
	// The by-ref $percent is passed, so similar_text() is impure (the flag is certain).
	return similar_text($a, $b, $percent);
}
