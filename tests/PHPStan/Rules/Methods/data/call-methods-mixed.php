<?php

namespace CallMethodsMixed;

class Baz
{
	/**
	 * @param mixed $value
	 */
	public function mixedMethod($value)
	{
		$value->fooMethod();

		$a = $value['a'];
		$a->fooMethod();
	}

}

class Foo
{
	public function fooMethod()
	{
		return 1;
	}
}
