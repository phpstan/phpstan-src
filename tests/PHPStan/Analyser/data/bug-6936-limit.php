<?php

namespace Bug6936Limits;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @return void
	 */
	public function testLimits():void
	{
		$arr2 = [1,2,3];

		$arr = [];
		if (rand(0,1)) {
			$arr[] = 'a';
		}
		if (rand(0,1)) {
			$arr[] = 'b';
		}
		if (rand(0,1)) {
			$arr[] = 'c';
		}
		if (rand(0,1)) {
			$arr[] = 'd';
		}
		if (rand(0,1)) {
			$arr[] = 'e';
		}
		if (rand(0,1)) {
			$arr[] = 'f';
		}
		if (rand(0,1)) {
			$arr[] = 'g';
		}

		assertType("array{0: 1|'a'|'b'|'c'|'d'|'e'|'f'|'g', 1: 2|'b', 2: 3|'c', 3?: 'd', 4?: 'e', 5?: 'f', 6?: 'g'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'h';
		}

		assertType("array{0: 1|'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h', 1: 2|'b', 2: 3|'c', 3?: 'd', 4?: 'e', 5?: 'f', 6?: 'g', 7?: 'h'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'i';
		}

		// fallback to a less precise form, which reduces the union-type size
		assertType("non-empty-array<0|1|2|3|4|5|6|7|8, 1|2|3|'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'>", $arr + $arr2);
	}
}
