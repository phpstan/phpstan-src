<?php

namespace FinalPrivateMethod;

class Foo
{

	final private function foo(): void
	{
	}

	final protected function bar(): void
	{
	}

	final public function baz(): void
	{
	}

	private function foobar(): void
	{
	}

}

class ConstructorsAreExcluded
{

	final private function __construct()
	{
	}

}

if (PHP_VERSION_ID >= 80000) {
	class FooBarPhp8orHigher
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID < 80000) {
	class FooBarPhp7
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID > 70400) {
	class FooBarPhp74OrHigher
	{

		final private function foo(): void
		{
		}
	}
}

if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 80100) {
	class FooBarBaz
	{

		final private function foo(): void
		{
		}
	}
}
