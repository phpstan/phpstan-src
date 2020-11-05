<?php

namespace Bug3915;

use function PHPStan\Analyser\assertType;

class HelloWorld
{

	public function sayHello(): void
	{
		$lengths = [0];
		foreach ([1] as $row) {
			$lengths[] = self::getInt();
		}
		assertType('array<int, int>&nonEmpty', $lengths);
	}

	public static function getInt(): int
	{
		return 5;
	}

}
