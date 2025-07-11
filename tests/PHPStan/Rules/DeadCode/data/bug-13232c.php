<?php // lint >= 8.0

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

	public function sayNullsafeHello(?self $x): void
	{
		echo 'Hello, ' . $x?->mightReturnNever()
			. ' no way';

		echo 'this might happen, in case $x is null';
	}

	public function sayMaybeHello(): void
	{
		if (rand(0, 1)) {
			echo 'Hello, ' . $this->mightReturnNever()
				. ' no way';
		}

		echo 'this might happen';
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
