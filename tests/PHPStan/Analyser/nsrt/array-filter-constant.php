<?php

namespace ArrayFilterConstantArray;

use function array_filter;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a: int}|array{b: string|null} $a
	 * @return void
	 */
	public function doFoo(array $a): void
	{
		assertType('array{a: int}|array{b: string|null}', $a);
		assertType('array{a?: int<min, -1>|int<1, max>}|array{b?: non-falsy-string}', array_filter($a));

		assertType('array{a: int}|array{b?: string}', array_filter($a, function ($v): bool {
			return $v !== null;
		}));

		$a = ['a' => 1, 'b' => null];
		assertType('array{a: 1}', array_filter($a, function ($v): bool {
			return $v !== null;
		}));

		assertType('array{a: 1}', array_filter($a));
	}

}
