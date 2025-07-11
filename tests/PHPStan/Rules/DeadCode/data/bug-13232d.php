<?php

namespace Bug13232d;

final class HelloWorld
{
	public function sayHi(): void
	{
		$x = 'Hello, ' . neverReturns()
			. ' no way';
		$x .= 'this will never happen';
	}
}
function neverReturns(): never {}

