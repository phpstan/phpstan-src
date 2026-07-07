<?php declare(strict_types = 1);

namespace MethodSignaturePureUnlessCallable;

interface PureUnlessParent
{

	/**
	 * @pure-unless-callable-is-impure $cb
	 */
	public function run(callable $cb): int;

}

class ImpureChild implements PureUnlessParent
{

	/**
	 * @phpstan-impure
	 */
	public function run(callable $cb): int
	{
		echo 'side effect';

		return $cb(1);
	}

}

class InheritingChild implements PureUnlessParent
{

	public function run(callable $cb): int
	{
		return $cb(1);
	}

}
