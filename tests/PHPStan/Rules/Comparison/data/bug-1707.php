<?php

namespace Bug1707;

class Test
{
	public function foo(): void
	{
		$values = ['a' => 1, 'b' => 2];
		$keys = ['a', 'b', 'c', 'd'];

		foreach ($keys as $key) {
			if(array_key_exists($key, $values)){
				unset($values[$key]);
			}

			if(0 === \count($values)) {
				break;
			}
		}

	}
}
