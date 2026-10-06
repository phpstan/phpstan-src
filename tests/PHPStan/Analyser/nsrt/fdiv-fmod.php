<?php declare(strict_types = 1);

namespace FdivFmodReturnType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param 10|20 $union
	 */
	public function doFoo(float $float, int $int, $union): void
	{
		assertType('2.5', fdiv(10, 4));
		assertType('2.5', fdiv(10.0, 4.0));
		assertType('-2.5', fdiv(-10, 4));
		assertType('2.0', fmod(10, 4));
		assertType('1.5', fmod(10.5, 3));
		assertType('-1.5', fmod(-10.5, 3));

		assertType('2.5|5.0', fdiv($union, 4));
		assertType('1.0|2.0', fmod($union, 3));

		// INF and NAN cannot be represented by a constant float type
		assertType('float', fdiv(1, 0));
		assertType('float', fdiv(0, 0));
		assertType('float', fmod(1, 0));

		assertType('float', fdiv($float, 4));
		assertType('float', fdiv(10, $int));
		assertType('float', fmod($float, 4.0));
	}

}
