<?php

declare(strict_types=1);

use function PHPStan\Testing\assertType;

define('VAL', 5);

class Bar
{
	public const VAL = 3;
}

class HelloWorld
{
	public function sayHello(string $s): void
	{
		if (preg_match('{(' . VAL . ')}', $s, $m)) {
			assertType('array{string, numeric-string}', $m);
		}
		if (preg_match('{(' . Bar::VAL . ')}', $s, $m)) {
			assertType('array{string, numeric-string}', $m);
		}
	}
}
