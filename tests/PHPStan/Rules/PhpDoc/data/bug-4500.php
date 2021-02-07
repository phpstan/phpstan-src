<?php

namespace Bug4500;

class Foo
{

	public function doFoo(): void
	{
		/** @var int */
		global $foo;
	}

	public function doBar(): void
	{
		/** @var int $foo */
		global $foo;
	}

	public function doBaz(): void
	{
		/** @var int */
		global $foo, $bar;
	}

	public function doLorem(): void
	{
		/** @var int $foo */
		global $foo, $bar;
	}

	public function doIpsum(): void
	{
		/**
		 * @var int $foo
		 * @var string $bar
		 */
		global $foo, $bar;

		$baz = 'foo';

		/** @var int $baz */
		global $lorem;
	}

	public function doDolor(): void
	{
		/** @var int $baz */
		global $lorem;
	}

}
