<?php

namespace Bug13232c;

final class HelloWorld
{
	public function sayHello(): void
	{
		echo 'Hello, ' . $this->mightReturnNever()
			. ' no way';

		echo 'this will never happen';
	}

	static public function sayStaticHello(): void
	{
		echo 'Hello, ' . self::staticMightReturnNever()
			. ' no way';

		echo 'this will never happen';
	}

	function mightReturnNever(): never

	{
		exit();
	}

	static function staticMightReturnNever(): never
	{
		exit();
	}

}
