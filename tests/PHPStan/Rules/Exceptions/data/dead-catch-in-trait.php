<?php

namespace DeadCatchInTrait;

class FooException extends \Exception
{

}

class BarException extends \Exception
{

}

trait FooTrait
{

	public function doFoo(): void
	{
		// sometimes thrown, sometimes not
		try {
			$this->doBar();
		} catch (FooException $e) {

		}
	}

	public function doFoo2(): void
	{
		// never thrown
		try {
			$this->doBar();
		} catch (BarException $e) {

		}
	}

	public function doFoo3(): void
	{
		// FooException sometimes thrown, BarException never
		try {
			$this->doBar();
		} catch (FooException | BarException $e) {

		}
	}

	public function doFoo4(): void
	{
		// already caught above in every context
		try {
			$this->doBar();
		} catch (\Exception $e) {

		} catch (FooException $e) {

		}
	}

}

class Foo
{

	use FooTrait;

	/**
	 * @throws FooException
	 */
	public function doBar(): void
	{

	}

}

class FooAnother
{

	use FooTrait;

	/**
	 * @throws void
	 */
	public function doBar(): void
	{

	}

}
