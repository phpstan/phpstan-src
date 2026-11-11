<?php declare(strict_types = 1);

namespace ConflictingPurityTags;

/**
 * @param-out int $count
 * @phpstan-pure
 * @pure-unless-parameter-passed $count
 */
function pureWithParameterPassed(string $subject, int &$count = 0): string
{
	return $subject;
}

/**
 * @param callable(string): string $cb
 * @phpstan-impure
 * @pure-unless-callable-is-impure $cb
 */
function impureWithCallable(callable $cb, string $subject): string
{
	return $cb($subject);
}

/**
 * @param-out int $count
 * @pure-unless-parameter-passed $count
 */
function onlyConditional(string $subject, int &$count = 0): string
{
	$count = 1;

	return $subject;
}

interface Replacer
{

	/**
	 * @param-out int $count
	 * @param callable(string): string $cb
	 * @impure
	 * @pure-unless-parameter-passed $count
	 * @pure-unless-callable-is-impure $cb
	 */
	public function both(callable $cb, string $subject, int &$count = 0): string;

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function onlyConditional(string $subject, int &$count = 0): string;

}

class Child implements Replacer
{

	/**
	 * The inherited @pure-unless-* tags are not part of this docblock.
	 *
	 * @phpstan-impure
	 */
	public function both(callable $cb, string $subject, int &$count = 0): string
	{
		echo $subject;

		return $cb($subject);
	}

	/**
	 * @phpstan-pure
	 */
	public function onlyConditional(string $subject, int &$count = 0): string
	{
		return $subject;
	}

}

/**
 * @param callable(string): string $cb
 * @phpstan-pure
 * @pure-unless-callable-is-impure $cb
 */
function pureWithCallable(callable $cb, string $subject): string
{
	return $cb($subject);
}

/**
 * @param callable(string): string $cb
 * @psalm-pure
 * @phpstan-pure-unless-callable-is-impure $cb
 */
function prefixedTags(callable $cb, string $subject): string
{
	return $cb($subject);
}

/**
 * @param callable(string): string $cb
 * @phpstan-pure
 * @phpstan-impure
 * @pure-unless-callable-is-impure $cb
 */
function pureAndImpure(callable $cb, string $subject): string
{
	return $cb($subject);
}
