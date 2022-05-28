<?php

namespace NoNamedArguments;

use function PHPStan\Testing\assertType;

/**
 * @no-named-arguments
 */
function noNamedArgumentsInFunction(float ...$args)
{
	assertType('array<int, float>', $args);
}

class Baz extends Foo implements Bar
{
	/**
	 * @no-named-arguments
	 */
	public function noNamedArgumentsInMethod(float ...$args)
	{
		assertType('array<int, float>', $args);
	}

	public function noNamedArgumentsInParent(float ...$args)
	{
		assertType('array<int, float>', $args);
	}

	public function noNamedArgumentsInInterface(float ...$args)
	{
		assertType('array<int, float>', $args);
	}
}

abstract class Foo
{
	/**
	 * @no-named-arguments
	 */
	abstract public function noNamedArgumentsInParent(float ...$args);
}

interface Bar
{
	/**
	 * @no-named-arguments
	 */
	public function noNamedArgumentsInInterface();
}
