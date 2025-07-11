<?php // lint >= 8.0

namespace Bug13232c;

final class HelloWorld
{
	public function sayHello(): void
	{
		echo 'Hello, ' . $this->returnNever()
			. ' no way';

		echo 'this will never happen';
	}

	static public function sayStaticHello(): void
	{
		echo 'Hello, ' . self::staticReturnNever()
			. ' no way';

		echo 'this will never happen';
	}

	public function sayNullsafeHello(?self $x): void
	{
		echo 'Hello, ' . $x?->returnNever()
			. ' no way';

		echo 'this might happen, in case $x is null';
	}

	public function sayMaybeHello(): void
	{
		if (rand(0, 1)) {
			echo 'Hello, ' . $this->returnNever()
				. ' no way';
		}

		echo 'this might happen';
	}

	function returnNever(): never

	{
		exit();
	}

	static function staticReturnNever(): never
	{
		exit();
	}

}
