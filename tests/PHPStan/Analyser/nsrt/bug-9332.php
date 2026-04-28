<?php declare(strict_types = 1);

namespace Bug9332;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		$data = ['a' => 'asdfghi'];
		foreach (['b', 'c'] as $x) {
			foreach (['d', 'e'] as $y) {
				$data[$x . $y] = mt_rand(1, 1000);
			}
		}

		assertType("array{a: 'asdfghi', bd: int<1, 1000>, be: int<1, 1000>, cd: int<1, 1000>, ce: int<1, 1000>}", $data);
		$this->doSomething($data);
	}

	/**
	 * @param array{a: string, bd: int, be: int, cd: int, ce: int} $a
	 */
	private function doSomething(array $a): void
	{

	}
}
