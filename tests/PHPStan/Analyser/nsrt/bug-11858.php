<?php declare(strict_types = 1);

namespace Bug11858;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayBar(): void
	{
		$arr = [];
		if (rand(0,1)) {
			$arr[] = 'A';
		}
		if (rand(0,1)) {
			$arr[] = 'B';
		}
		if (rand(0,1)) {
			$arr[] = 'C';
		}

		assertType("array{}|array{'C'}|array{0: 'B', 1?: 'C'}|list{0: 'A', 1?: 'B'|'C', 2?: 'C'}", $arr);

		if (count($arr) >= 2 && $arr[0] === 'A') {
			if (array_key_exists(1, $arr) && $arr[1] === 'C') {
				assertType("array{0: 'A', 1: 'C', 2?: 'C'}", $arr);
			}
		}
	}
}
