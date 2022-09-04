<?php

namespace Bug7921;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param list<array{a: string, b:string, c:string|null}> $arr */
	public function sayHello(array $arr): void
	{
		$pre_computed_arr = [
			'a' => null,
			'b' => null,
			'c' => null,
		];

		foreach ($arr as $arr_val) {
			$pre_computed_arr['a'] = $arr_val['a'];
			$pre_computed_arr['b'] = $arr_val['b'];
			$pre_computed_arr['c'] = $arr_val['c'];
		}

		assertType('string|null', $pre_computed_arr['a']);
		assertType('string|null', $pre_computed_arr['b']);
		assertType('string|null', $pre_computed_arr['c']);

		if ($pre_computed_arr['a'] === null) {
			assertType('null', $pre_computed_arr['a']);
			assertType('null', $pre_computed_arr['b']);
			assertType('null', $pre_computed_arr['c']);
			return;
		}

		assertType('string', $pre_computed_arr['a']);
		assertType('string', $pre_computed_arr['b']);
		assertType('string|null', $pre_computed_arr['c']);
	}

}
