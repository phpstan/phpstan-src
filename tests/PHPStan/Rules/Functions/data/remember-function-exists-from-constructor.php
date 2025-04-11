<?php // lint >= 7.4

namespace RememberFunctionExistsFromConstructor;

class User
{
	public function __construct(
	) {
		if (!function_exists('some_unknown_function')) {
			throw new \LogicException();
		}
	}

	public function doFoo(): void
	{
		some_unknown_function();
	}

}
