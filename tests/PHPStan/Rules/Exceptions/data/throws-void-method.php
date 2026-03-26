<?php

namespace ThrowsVoidMethod;

class MyException extends \Exception
{

}

class Foo
{

	/**
	 * @throws void
	 */
	public function doFoo(): void
	{
		throw new MyException();
	}


}
