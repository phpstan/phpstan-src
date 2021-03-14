<?php

namespace Bug2164;

class A
{
	/**
	 * @param static|string $arg
	 * @return void
	 */
	public static function staticTest($arg)
	{
	}
}

class B extends A
{
	/**
	 * @param B|string $arg
	 * @return void
	 */
	public function test($arg)
	{
		B::staticTest($arg);
	}
}

final class B2 extends A
{
	/**
	 * @param B2|string $arg
	 * @return void
	 */
	public function test($arg)
	{
		B2::staticTest($arg);
	}
}
