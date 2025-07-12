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

	public function sayHo(): void
	{
		echo "Hello, {$this->neverReturnsMethod()} no way";
		echo 'this will never happen';
	}

	function neverReturnsMethod(): never {}
}
function neverReturns(): never {}

