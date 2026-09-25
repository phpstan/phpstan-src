<?php

namespace InvalidPHPStanDoc;

class Baz{}
/** @phpstan-extens Baz */
class Boo extends Baz
{
	/**
	 * @phpstan-template T
	 * @phpstan-pararm class-string<T> $a
	 * @phpstan-return T
	 */
	function foo(string $a){}

	/**
	 * @phpstan-ignore-next-line
	 * @phpstan-pararm
	 */
	function bar()
	{

	}

	function baz()
	{
		/** @phpstan-va */$a = $b; /** @phpstan-ignore-line */
		$c = 'foo';
	}

	/**
	 * @phpstan-throws void
	 */
	function any()
	{

	}
}

class AboveProperty
{

	/** @phpstan-varr 1 */
	private $foo;

	/** @phpstan-varr 1 */
	private const TEST = 1;

}

class AboveReturn
{

	public function doFoo(): string
	{
		/** @phpstan-varr string */
		return doFoo();
	}

}

/**
 * @param-out int $count
 * @phpstan-pure-unless-callable-is-impure $callback
 * @phpstan-pure-unless-parameter-passed $count
 */
function withPurityTags(callable $callback, int &$count = 0): void
{
}
