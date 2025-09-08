<?php

namespace Bug2457;

class HelloWorld
{
	public function sayHello(array $x, array $y): void
	{
		$a = [];
		$o = [];

		foreach ($x as $t) {
			if (!isset($a[$t])) {
				$a[$t] = [];
			}
			$o[] = 'x';
			array_unshift($a[$t], count($o) - 1);
		}

		foreach ($y as $t) {
			if (!isset($a[$t])) {
				continue;
			}
			foreach ($a[$t] as $b) {
				// this will be reached
			}
		}
	}
}
