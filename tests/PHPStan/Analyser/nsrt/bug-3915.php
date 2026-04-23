<?php

namespace Bug3915;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function sayHello(): void
	{
		$lengths = [0];
		foreach ([1] as $row) {
			$lengths[] = self::getInt();
		}
		assertType('array{0, int}', $lengths);
	}

	public static function getInt(): int
	{
		return 5;
	}

}

class HelloWorld2
{

	/**
	 * @param non-empty-list<int> $rows
	 */
	public function sayHello(array $rows): void
	{
		$lengths = [0];
		foreach ($rows as $row) {
			$lengths[] = self::getInt();
		}
		assertType('non-empty-list<int>', $lengths);
	}

	public static function getInt(): int
	{
		return 5;
	}

}
