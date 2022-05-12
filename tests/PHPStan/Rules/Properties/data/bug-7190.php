<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug7190;

interface MyObject {
	public function getId(): int;
}

class HelloWorld
{
	/**
	 * @param array<int, int> $array
	 */
	public function sayHello(array $array, MyObject $object): int
	{
		if (!isset($array[$object->getId()])) {
			return 1;
		}

		return $array[$object->getId()] ?? 2;
	}
}
