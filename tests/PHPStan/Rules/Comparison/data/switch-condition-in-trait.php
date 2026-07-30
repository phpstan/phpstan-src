<?php

namespace SwitchConditionInTrait;

trait FooTrait
{

	public function doFoo(): void
	{
		// sometimes constant, sometimes not
		switch (true) {
			case $this->doBar():
				break;
		}
	}

	public function doFoo2(): void
	{
		// always false
		switch (true) {
			case $this->doBar2():
				break;
		}
	}

	public function doFoo3(): void
	{
		// always true
		switch (true) {
			case $this->doBar3():
				break;
			case $this->doBar3():
				break;
			default:
				break;
		}
	}

}

class Foo
{

	use FooTrait;

	/**
	 * @return false
	 */
	public function doBar(): bool
	{

	}

	/**
	 * @return false
	 */
	public function doBar2(): bool
	{

	}

	/**
	 * @return true
	 */
	public function doBar3(): bool
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): bool
	{

	}

	/**
	 * @return false
	 */
	public function doBar2(): bool
	{

	}

	/**
	 * @return true
	 */
	public function doBar3(): bool
	{

	}

}
