<?php declare(strict_types = 1);

namespace Substracted;


use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(mixed $date, bool $foo): void
	{
		if(is_object($date)){

		} else {
			assertType('mixed~object', $date);

			if ($foo) {
				$date = new \stdClass();
			}
			assertType('mixed~object~stdClass', $date);

			if (is_object($date)) {
				assertType('stdClass', $date);
			}
		}
	}
}
