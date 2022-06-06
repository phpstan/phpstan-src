<?php

namespace NoNamedArguments;

class Baz extends Foo implements Bar
{
	/**
	 * @no-named-arguments
	 */
	public function namedArgumentsInParent(float ...$args) {}

	/**
	 * @no-named-arguments
	 */
	public function namedArgumentsInInterface(float ...$args) {}
}

abstract class Foo
{
	abstract public function namedArgumentsInParent(float ...$args);
}

interface Bar
{
	public function namedArgumentsInInterface();
}
