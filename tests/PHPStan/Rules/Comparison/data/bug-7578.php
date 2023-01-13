<?php

namespace Bug7578;

class HelloWorld
{
	/**
	 * @param non-empty-array<mixed> $array
	 */
	public function foo(array $array): void
	{
		if ([] === $array) {
			throw new \InvalidArgumentException();
		}
	}

	/**
	 * @param non-empty-array<mixed> $array
	 */
	public function foo2(array $array): void
	{
		if (0 === count($array)) {
			throw new \InvalidArgumentException();
		}
	}
}
