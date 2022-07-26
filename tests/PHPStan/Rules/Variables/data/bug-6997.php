<?php

namespace Bug6997;

class HelloWorld
{
	public function sayHello(array $foo)
	{
		$whereParts = [];

		if (!$foo['a']) {
			$whereParts[] = 'something';
		}

		if (!$foo['b']) {
			$count = 3;
			$whereParts[] = 'example';
		}

		if (empty($whereParts)) {
			return [];
		}

		return isset($count) ? $count : 0;
	}
}
