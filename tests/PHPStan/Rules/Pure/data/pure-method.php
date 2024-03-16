<?php

namespace PureMethod;

class Foo
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(&$p)
	{
		echo 'test';
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo2(): void
	{
		die;
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo3(object $obj)
	{
		$obj->foo = 'test';
	}


}
