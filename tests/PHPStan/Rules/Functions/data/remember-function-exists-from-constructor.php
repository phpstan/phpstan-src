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

class FooUser
{
	public function __construct(
	) {
		if (!function_exists('another_unknown_function')) {
			echo 'Function another_unknown_function does not exist';
		}
	}

	public function doFoo(): void
	{
		another_unknown_function();
	}

}
