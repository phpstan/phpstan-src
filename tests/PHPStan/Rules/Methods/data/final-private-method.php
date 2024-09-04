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
