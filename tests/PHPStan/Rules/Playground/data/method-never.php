<?php

namespace MethodNever;

class Foo
{

	public function doFoo(): never
	{
		throw new \Exception();
	}

	/**
	 * @return never
	 */
	public function doFoo2()
	{
		throw new \Exception();
	}

	public function doBar(): void
	{
		throw new \Exception();
	}

	public function callsNever()
	{
		$this->doFoo();
	}

	public function doBaz()
	{
		while (true) {

		}
	}

	public function onlySometimes()
	{
		if (rand(0, 1)) {
			return;
		}

		throw new \Exception();
	}

	/**
	 * @return \Generator<int, int, null, never>
	 */
	public function yields(): \Generator
	{
		while(true) {
			yield 1;
		}
	}

}
