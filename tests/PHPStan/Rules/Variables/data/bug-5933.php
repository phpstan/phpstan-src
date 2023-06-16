<?php

namespace Bug5933;

class HelloWorld
{
	public function sayHello(): void
	{
		$arr = [];

		for ($i = 0; $i < 100; $i++) {
			if (rand(0, 1) === 0) {
				$arr[$i]['a'] = $i;
			} else {
				$arr[$i]['b'] = $i;
			}
		}

		foreach($arr as $val) {
			var_dump($val['a'] ?? null);
		}

		// Surprisingly it doesn't have a problem with this
		for ($i = 0; $i < 100; $i++) {
			var_dump($arr[$i]['a'] ?? null);
		}
	}
}
