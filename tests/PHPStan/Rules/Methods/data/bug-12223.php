<?php

namespace Bug12223;

class HelloWorld
{
	/**
	 * @return list<string>
	 */
	public function sayHello(): array
	{
		$a = [1 => 'foo', 3 => 'bar', 5 => 'baz'];
		return array_map(static fn(string $s, int $i): string => $s . $i, $a, array_keys($a));
	}
}
