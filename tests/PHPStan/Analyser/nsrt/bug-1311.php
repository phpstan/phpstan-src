<?php declare(strict_types = 1);

namespace Bug1311;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<int, array{a: string}> $sets
	 *
	 * @return array<int, array{a: string, b: bool}>
	 */
	public function sayHello(array $sets): array
	{
		foreach ($sets as &$set) {
			$set['b'] = false;
		}

		assertType('array<int, array{a: string, b: false}>', $sets);

		return $sets;
	}
}
