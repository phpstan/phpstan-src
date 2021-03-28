<?php

namespace UnthrownException;

class Foo
{

	public function doFoo(): void
	{
		try {
			$foo = 1;
		} catch (\Throwable $e) {
			// pass
		}
	}

	public function doBar(): void
	{
		try {
			$foo = 1;
		} catch (\Exception $e) {
			// pass
		}
	}

	/** @throws \InvalidArgumentException */
	public function throwIae(): void
	{

	}

	public function doBaz(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException $e) {

		} catch (\Throwable $e) {
			// dead
		}
	}

	public function doLorem(): void
	{
		try {
			$this->throwIae();
		} catch (\RuntimeException $e) {
 			// dead
		} catch (\Throwable $e) {

		}
	}

	public function doIpsum(): void
	{
		try {
			$this->throwIae();
		} catch (\Throwable $e) {

		}
	}

	public function doDolor(): void
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {

		} catch (\Throwable $e) {

		}
	}

	public function doSit(): void
	{
		try {
			try {
				\ThrowPoints\Helpers\maybeThrows();
			} catch (\InvalidArgumentException $e) {

			}
		} catch (\InvalidArgumentException $e) {

		}
	}

}
