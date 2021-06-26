<?php // lint >= 7.4

namespace Bug4083;


class HelloWorld
{
	public function sayHello(): void
	{
		$items = [[1, 'a'], [2, 'b']];
		array_map(fn (array $x) => $this->takesShapedArray($x), $items);
		array_map(fn ($x) => $this->takesShapedArray($x), $items);
	}

	/**
	 * @param array{0:int,1:string} $x
	 */
	private function takesShapedArray(array $x): int { return 0; }
}
