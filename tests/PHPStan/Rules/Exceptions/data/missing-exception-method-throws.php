<?php

namespace MissingExceptionMethodThrows;

class Foo
{

	/** @throws \InvalidArgumentException */
	public function doFoo(): void
	{
		throw new \InvalidArgumentException(); // ok
	}

	/** @throws \LogicException */
	public function doBar(): void
	{
		throw new \InvalidArgumentException(); // ok
	}

	/** @throws \RuntimeException */
	public function doBaz(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	/** @throws \RuntimeException */
	public function doLorem(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	public function doLorem2(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	public function doLorem3(): void
	{
		try {
			throw new \InvalidArgumentException(); // ok
		} catch (\InvalidArgumentException $e) {

		}
	}

	public function doIpsum(): void
	{
		throw new \PHPStan\ShouldNotHappenException(); // ok
	}

	public function doDolor(): void
	{
		try {
			doFoo();
		} catch (\Throwable $e) {
			throw $e;
		}
	}

}
