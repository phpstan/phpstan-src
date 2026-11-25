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
	// The by-ref $count is passed, but it is a local variable, so the write is not observable.
	$count = 0;

	return str_replace('a', 'b', $s, $count);
}

/**
 * @phpstan-pure
 */
function purePregMatchWithoutMatches(string $s): int
{
	// The by-ref $matches is omitted, so preg_match() is pure. The error state it sets is
	// read by preg_last_error(), whose result is nondeterministic and never remembered.
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
	// The by-ref $count is passed, but it is a local variable, so the write is not observable.
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
	// The by-ref $percent is passed, but it is a local variable, so the write is not observable.
	return similar_text($a, $b, $percent);
}

final class Counter
{

	public int $count = 0;

}

/**
 * @phpstan-pure
 */
function pureStrReplaceWithCountProperty(string $s, Counter $counter): string
{
	// The by-ref $count is an object's property, so the write is observable.
	return str_replace('a', 'b', $s, $counter->count);
}
