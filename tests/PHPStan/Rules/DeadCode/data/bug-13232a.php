<?php

namespace Bug13232a;

final class HelloWorld
{
	public function sayHi(): void
	{
		echo 'Hello, ' . neverReturns()
			. ' no way';
		echo 'this will never happen';
	}
}
function neverReturns(): never {}

