<?php

namespace ConstantArrayTypeSet;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(int $i)
	{
		$a = [1, 2, 3];
		$a[$i] = 4;
		assertType('non-empty-array<int, 1|2|3|4>', $a);

		$b = [1, 2, 3];
		$b[3] = 4;
		assertType('array{1, 2, 3, 4}', $b);

		$c = [false, false, false];
		/** @var 0|1|2 $offset */
		$offset = doFoo();
		$c[$offset] = true;
		assertType('array{bool, bool, bool}', $c);

		$d = [false, false, false];
		/** @var int<0, 2> $offset2 */
		$offset2 = doFoo();
		$d[$offset2] = true;
		//assertType('array{bool, bool, bool}', $d);

		$e = [false, false, false];
		/** @var 0|1|2|3 $offset3 */
		$offset3 = doFoo();
		$e[$offset3] = true;
		assertType('non-empty-array<0|1|2|3, bool>', $e);

		$f = [false, false, false];
		/** @var 0|1 $offset4 */
		$offset4 = doFoo();
		$f[$offset4] = true;
		assertType('array{bool, bool, false}', $f);
	}

}
