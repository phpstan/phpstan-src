<?php

namespace Cleaning;

class Foo
{

	public function doFoo()
	{
		test();
	}

}

interface Bar
{

	public function doBar();

}

class Baz
{

	public function someGenerator()
	{
		if (rand(0, 1)) {
			yield;
		}
	}

	public function someGenerator2()
	{
		if (rand(0, 1)) {
			yield from [1, 2, 3];
		}
	}

	public function someVariadics()
	{
		if (rand(0, 1)) {
			func_get_args();
		}
	}

	public function both()
	{
		if (rand(0, 1)) {
			yield;
		}
		if (rand(0, 1)) {
			func_get_args();
		}
	}

}

class InlineVars
{
	public function doFoo()
	{
		/** @var Test */
		$foo = doFoo();

		/** @var Test2 */
		$foo = doFoo();

		/** @var Test3 */
		$foo = doFoo();

		/** @phpstan-var Test */
		$foo = doFoo();

		/** @psalm-var Test */
		$foo = doFoo();

		if (rand(0, 1)) {
			yield;
		}
		if (rand(0, 1)) {
			func_get_args();
		}
	}
}
