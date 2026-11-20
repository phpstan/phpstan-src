<?php declare(strict_types = 1);

namespace WalkTracePureUnlessAllMethodsPure;

// a @pure-unless-* tag, written or inherited, takes precedence over the
// class-level @phpstan-all-methods-pure / -impure in the method's PHPDocs

/**
 * @phpstan-all-methods-pure
 */
class Replacer
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

	/**
	 * @param callable(string): string $cb
	 * @pure-unless-callable-is-impure $cb
	 */
	public function map(callable $cb, string $subject): string
	{
		return $cb($subject);
	}

}

interface ReplacerInterface
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count = 0): string;

	/**
	 * @param callable(string): string $cb
	 * @pure-unless-callable-is-impure $cb
	 */
	public function map(callable $cb, string $subject): string;

}

/**
 * @phpstan-all-methods-pure
 */
class InheritingReplacer implements ReplacerInterface
{

	public function replace(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

	public function map(callable $cb, string $subject): string
	{
		return $cb($subject);
	}

}

/**
 * @phpstan-all-methods-impure
 */
class ImpureClassInheritingReplacer implements ReplacerInterface
{

	public function replace(string $subject, int &$count = 0): string
	{
		echo $subject;
		$count = 1;

		return $subject;
	}

	public function map(callable $cb, string $subject): string
	{
		echo $subject;

		return $cb($subject);
	}

}

/**
 * @phpstan-pure
 */
function omittingCount(Replacer $r, InheritingReplacer $i, string $s): string
{
	// $count is omitted, so the calls stay pure.
	return $r->replace($s) . $i->replace($s);
}

/**
 * @phpstan-pure
 */
function passingCount(Replacer $r, InheritingReplacer $i, string $s): string
{
	$c = 0;
	$d = 0;
	// $count is passed, so the calls are impure although the classes are marked
	// @phpstan-all-methods-pure.
	return $r->replace($s, $c) . $i->replace($s, $d);
}

/**
 * @phpstan-pure
 */
function pureCallback(Replacer $r, InheritingReplacer $i, string $s): string
{
	return $r->map(static fn (string $x): string => $x, $s) . $i->map(static fn (string $x): string => $x, $s);
}

/**
 * @phpstan-pure
 */
function impureCallback(Replacer $r, InheritingReplacer $i, string $s): string
{
	// The callback is impure, so the calls are impure although the classes are
	// marked @phpstan-all-methods-pure.
	$cb = static function (string $x): string {
		echo $x;

		return $x;
	};

	return $r->map($cb, $s) . $i->map($cb, $s);
}
