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

		assertType("array{0: 'a', 1: 2|'b'|'c'|'d'|'e'|'f'|'g', 2: 3|'c'|'d'|'e'|'f'|'g', 3?: 'd'|'e'|'f'|'g', 4?: 'e'|'f'|'g', 5?: 'f'|'g', 6?: 'g'}|array{0: 'b', 1: 2|'c'|'d'|'e'|'f'|'g', 2: 3|'d'|'e'|'f'|'g', 3?: 'e'|'f'|'g', 4?: 'f'|'g', 5?: 'g'}|array{0: 'c', 1: 2|'d'|'e'|'f'|'g', 2: 3|'e'|'f'|'g', 3?: 'f'|'g', 4?: 'g'}|array{0: 'd', 1: 2|'e'|'f'|'g', 2: 3|'f'|'g', 3?: 'g'}|array{1|'e'|'f'|'g', 2|'f'|'g', 3|'g'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'h';
		}

		assertType("array{0: 'a', 1: 2|'b'|'c'|'d'|'e'|'f'|'g'|'h', 2: 3|'c'|'d'|'e'|'f'|'g'|'h', 3?: 'd'|'e'|'f'|'g'|'h', 4?: 'e'|'f'|'g'|'h', 5?: 'f'|'g'|'h', 6?: 'g'|'h', 7?: 'h'}|array{0: 'b', 1: 2|'c'|'d'|'e'|'f'|'g'|'h', 2: 3|'d'|'e'|'f'|'g'|'h', 3?: 'e'|'f'|'g'|'h', 4?: 'f'|'g'|'h', 5?: 'g'|'h', 6?: 'h'}|array{0: 'c', 1: 2|'d'|'e'|'f'|'g'|'h', 2: 3|'e'|'f'|'g'|'h', 3?: 'f'|'g'|'h', 4?: 'g'|'h', 5?: 'h'}|array{0: 'd', 1: 2|'e'|'f'|'g'|'h', 2: 3|'f'|'g'|'h', 3?: 'g'|'h', 4?: 'h'}|array{0: 'e', 1: 2|'f'|'g'|'h', 2: 3|'g'|'h', 3?: 'h'}|array{1|'f'|'g'|'h', 2|'g'|'h', 3|'h'}", $arr + $arr2);
		if (rand(0,1)) {
			$arr[] = 'i';
		}

		assertType("array{0: 'a', 1: 2|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i', 2: 3|'c'|'d'|'e'|'f'|'g'|'h'|'i', 3?: 'd'|'e'|'f'|'g'|'h'|'i', 4?: 'e'|'f'|'g'|'h'|'i', 5?: 'f'|'g'|'h'|'i', 6?: 'g'|'h'|'i', 7?: 'h'|'i', 8?: 'i'}|array{0: 'b', 1: 2|'c'|'d'|'e'|'f'|'g'|'h'|'i', 2: 3|'d'|'e'|'f'|'g'|'h'|'i', 3?: 'e'|'f'|'g'|'h'|'i', 4?: 'f'|'g'|'h'|'i', 5?: 'g'|'h'|'i', 6?: 'h'|'i', 7?: 'i'}|array{0: 'c', 1: 2|'d'|'e'|'f'|'g'|'h'|'i', 2: 3|'e'|'f'|'g'|'h'|'i', 3?: 'f'|'g'|'h'|'i', 4?: 'g'|'h'|'i', 5?: 'h'|'i', 6?: 'i'}|array{0: 'd', 1: 2|'e'|'f'|'g'|'h'|'i', 2: 3|'f'|'g'|'h'|'i', 3?: 'g'|'h'|'i', 4?: 'h'|'i', 5?: 'i'}|array{0: 'e', 1: 2|'f'|'g'|'h'|'i', 2: 3|'g'|'h'|'i', 3?: 'h'|'i', 4?: 'i'}|array{0: 'f', 1: 2|'g'|'h'|'i', 2: 3|'h'|'i', 3?: 'i'}|array{1|'g'|'h'|'i', 2|'h'|'i', 3|'i'}", $arr + $arr2);
	}
}
