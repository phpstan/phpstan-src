<?php declare(strict_types = 1);

namespace Bug12880;

class HelloWorld
{
	public function sayHello(): void
	{
		$this->test([1, 2, 3, true]);
	}

	/**
	 * @param  list<mixed>  $ids
	 */
	private function test(array $ids): void
	{
		$ids = array_unique($ids);
		\PHPStan\dumpType($ids);
		$ids = array_slice($ids, 0, 5);
		\PHPStan\dumpType($ids);
		$this->expectList($ids);
	}

	/**
	 * @param  list<mixed>  $ids
	 */
	private function expectList(array $ids): void
	{
		var_dump($ids);
	}
}
