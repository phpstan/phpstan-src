<?php

namespace TrickyCallables;

class Foo
{

	/**
	 * @param callable(string): void $cb
	 */
	public function doFoo(callable $cb)
	{
		$this->doBar($cb);
	}

	/**
	 * @param callable(string|null): void $cb
	 */
	public function doBar(callable $cb)
	{

	}

}

class Bar
{

	/**
	 * @param callable(string): void $cb
	 */
	public function doFoo(callable $cb)
	{
		$this->doBar($cb);
	}

	/**
	 * @param callable(string=): void $cb
	 */
	public function doBar(callable $cb)
	{

	}

}

class Baz
{

	/**
	 * @param callable(string): void $cb
	 */
	public function doFoo(callable $cb)
	{
		$this->doBar($cb);
	}

	/**
	 * @param callable(): void $cb
	 */
	public function doBar(callable $cb)
	{

	}

}

final class TwoErrorsAtOnce
{
	/**
	 * @param callable(string|int $key=): bool $filter
	 */
	public function run(callable $filter): void
	{
	}
}

function (TwoErrorsAtOnce $t): void {
	$filter = static fn (): bool => true;
	$t->run($filter);

	$filter = static fn (int $key): bool => true;
	$t->run($filter);
};
