<?php

namespace Bug5005;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(
		int|false $int1,
		int|false $int2,
		int|null $int3,
		int|null $int4,
		int $int5,
		int $int6,
		float|int|null $floatOrIntOrNull,
	): void
	{
		assertType('(float|int)', $int1 / $int2);
		assertType('(float|int)', $int3 / $int4);
		assertType('(float|int)', $int5 / $int6);

		assertType('(float|int)', $floatOrIntOrNull / $int6);
	}

	/**
	 * @param float|int<1, 4> $floatOrIntRange
	 * @param int<1, 4>       $intRange
	 */
	public function doBar(
		float|int $floatOrInt,
		float|int $floatOrIntRange,
		float $float,
		int $int,
		int $intRange,
		mixed $mixed
	) {
		assertType('float|int', $floatOrInt / 1);
		assertType('float|int<1, 4>', $floatOrIntRange / 1);
		assertType('float|int', $mixed / 1);
		assertType('float', $float / 1);
		assertType('int', $int / 1);
		assertType('int<1, 4>', $intRange / 1);

		assertType('(float|int)', $floatOrInt / 2);
		assertType('float|int<0, 2>', $floatOrIntRange / 2);
		assertType('(float|int)', $mixed / 2);
		assertType('float', $float / 2);
		assertType('(float|int)', $int / 2);
		assertType('float|int<0, 2>', $intRange / 2);

		assertType('(float|int)', $floatOrInt / $int);
		assertType('(float|int)', $floatOrIntRange / $int);
		assertType('(float|int)', $mixed / $int);
		assertType('float', $float / $int);
		assertType('(float|int)', $int / $int);
		assertType('(float|int)', $intRange / $int);

		assertType('float', $floatOrInt / $float);
		assertType('float', $floatOrIntRange / $float);
		assertType('float', $mixed / $float);
		assertType('float', $float / $float);
		assertType('float', $int / $float);
		assertType('float', $intRange / $float);

		assertType('(float|int)', $floatOrInt / $floatOrInt);
		assertType('(float|int)', $floatOrIntRange / $floatOrInt);
		assertType('(float|int)', $mixed / $floatOrInt);
		assertType('float', $float / $floatOrInt);
		assertType('(float|int)', $int / $floatOrInt);
		assertType('(float|int)', $intRange / $floatOrInt);
	}

}
