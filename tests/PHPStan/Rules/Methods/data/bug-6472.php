<?php

namespace Bug6472;

trait A
{
	/**
	 * @param mixed[] $array
	 */
	public static function a(array $array): void
	{
		new class {

		};
	}

	/**
	 * @param mixed[] $array
	 */
	public static function b(array $array): void
	{
	}

}

class B
{
	use A;
}
