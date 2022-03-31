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

		assertType("array{0: 1|'a'|'b'|'c'|'d'|'e'|'f'|'g', 1: 2|'b'|'c'|'d'|'e'|'f'|'g', 2: 3|'c'|'d'|'e'|'f'|'g', 3?: 'd'|'e'|'f'|'g', 4?: 'e'|'f'|'g', 5?: 'f'|'g', 6?: 'g'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'h';
		}

		assertType("array{0: 1|'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h', 1: 2|'b'|'c'|'d'|'e'|'f'|'g'|'h', 2: 3|'c'|'d'|'e'|'f'|'g'|'h', 3?: 'd'|'e'|'f'|'g'|'h', 4?: 'e'|'f'|'g'|'h', 5?: 'f'|'g'|'h', 6?: 'g'|'h', 7?: 'h'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'i';
		}

		assertType("array{0: 1|'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i', 1: 2|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i', 2: 3|'c'|'d'|'e'|'f'|'g'|'h'|'i', 3?: 'd'|'e'|'f'|'g'|'h'|'i', 4?: 'e'|'f'|'g'|'h'|'i', 5?: 'f'|'g'|'h'|'i', 6?: 'g'|'h'|'i', 7?: 'h'|'i', 8?: 'i'}", $arr + $arr2);
	}
}
