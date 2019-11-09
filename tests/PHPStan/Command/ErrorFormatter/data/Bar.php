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
	 * @param
	 *            $object
	 */
	public function phpdocWithNewlines($object) {
	}
}
