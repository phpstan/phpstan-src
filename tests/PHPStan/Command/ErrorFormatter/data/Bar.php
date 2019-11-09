<?php

namespace BaselineIntegration;

class Bar
{

	use FooTrait;

	/**
	 * @return array<array<int>>
	 */
	public function doFoo(): array
	{
		return [['foo']];
	}

	/**
	 * The following phpdoc is invalid and should trigger a error message containing newlines.
	 *
	 * @param
	 *            $object
	 */
	public function phpdocWithNewlines($object) {
	}
}
