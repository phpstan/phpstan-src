<?php

namespace Bug4455Static;

class HelloWorld
{
	public function sayHello(string $_): bool
	{
		if($_ ===''){
			return true;
		}

		self::nope();
	}

	/**
	 * @psalm-pure
	 * @return never
	 */
	public static function nope() {
		throw new \Exception();
	}
}
