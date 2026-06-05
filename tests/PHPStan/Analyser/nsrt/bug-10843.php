<?php declare(strict_types = 1);

namespace Bug10843;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(HelloWorld|null $date): int
	{
		$a = match (true) {
			$date instanceof HelloWorld => 1,
			default => false
		};

		if ($date instanceof HelloWorld) {
			assertType('1', $a);
			return $a;
		}

		throw new \Exception('Error');
	}
}
