<?php

namespace Bug3576;

class Foo
{

	public function doFoo(): void
	{
		if (\function_exists('bug3576')) {
			bug3576();
			\bug3576();
		} else {
			bug3576();
		}

		bug3576();
	}

	public function doBar(): void
	{
		if (function_exists('bug3576')) {
			bug3576();
			\bug3576();
		} else {
			bug3576();
		}

		bug3576();
	}

	public function doBaz(): void
	{
		if (function_exists('\bug3576')) {
			bug3576();
			\bug3576();
		} else {
			bug3576();
		}

		bug3576();
	}

}

