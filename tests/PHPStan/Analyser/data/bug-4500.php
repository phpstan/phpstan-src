<?php

namespace Bug4500TypeInference;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFirst(): void
	{
		global $foo;
		assertType('mixed', $foo);
	}

	public function doFoo(): void
	{
		/** @var int */
		global $foo;
		assertType('int', $foo);
	}

	public function doBar(): void
	{
		/** @var int $foo */
		global $foo;
		assertType('int', $foo);
	}

	public function doBaz(): void
	{
		/** @var int */
		global $foo, $bar;
		assertType('mixed', $foo);
		assertType('mixed', $bar);
	}

	public function doLorem(): void
	{
		/** @var int $foo */
		global $foo, $bar;
		assertType('int', $foo);
		assertType('mixed', $bar);

		$baz = 'foo';

		/** @var int $baz */
		global $lorem;
		assertType('mixed', $lorem);
		assertType('\'foo\'', $baz);
	}

	public function doIpsum(): void
	{
		/**
		 * @var int $foo
		 * @var string $bar
		 */
		global $foo, $bar;

		assertType('int', $foo);
		assertType('string', $bar);
	}

	public function doDolor(): void
	{
		/** @var int $baz */
		global $lorem;

		assertType('mixed', $lorem);
		assertType('*ERROR*', $baz);
	}

}
