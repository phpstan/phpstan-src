<?php

namespace Bug5872;

class HelloWorld
{
	/**
	 * @phpstan-param mixed[] $mixedArray
	 */
	public function sayHello(array $mixedArray): void
	{
		var_dump(array_map('\strval', $mixedArray["api"]));
		var_dump(array_map('\strval', (array) $mixedArray["api"]));
	}
}


/**
 * @implements \IteratorAggregate<int>
 */
class FooIterator implements \IteratorAggregate
{
	/**
	 * @return \Generator<int>
	 */
	public function getIterator(): \Generator
	{
		yield 1;
		yield 2;
		yield 3;
	}
}

function (): void {
	\array_map(
		static function (int $i): string {
			return (string) $i;
		},
		\iterator_to_array(new FooIterator())
	);
};
