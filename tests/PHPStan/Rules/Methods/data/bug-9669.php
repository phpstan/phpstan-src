<?php

namespace Bug9669;

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

		return $sets;
	}
}
