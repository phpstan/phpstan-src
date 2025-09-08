<?php

namespace Bug2560;

class HelloWorld
{
	public function test(): void
	{
		$arr = [];
		foreach ([0,1] as $i) {
			$arr[$i] = [];
			array_push($arr[$i], "foo");
		}
		foreach (array_values($arr) as $vec) {
			foreach ($vec as $value) {
				print_r("$value");
			}
		}
	}
}
