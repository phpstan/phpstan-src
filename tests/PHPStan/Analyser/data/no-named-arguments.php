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

class Baz
{
	/**
	 * @no-named-arguments
	 */
	public function noNamedArgumentsInMethod(float ...$args)
	{
		assertType('array<int, float>', $args);
	}
}
