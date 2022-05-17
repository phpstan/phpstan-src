<?php

namespace ConstantArrayTypeIdentical;

use function PHPStan\Testing\assertType;

class Foo
{


	public function doFoo(string $s): void
	{
		assertType('true', [1] === [1]);
		assertType('true', [1] == [1]);
		assertType('false', [1] != [1]);
		assertType('false', [1] == [2]);
		assertType('true', [1] != [2]);
		assertType('true', [1] == ["1"]);
		assertType('false', [1] != ["1"]);

		assertType('false', [1] === [2]);
		assertType('false', [1] !== [1]);
		assertType('true', [1] !== [2]);
		assertType('true', [$s] === [$s]);
		assertType('false', [$s] !== [$s]);

		$a = [1];
		if (doFoo()) {
			$a[] = 2;
		}

		assertType('bool', [1] === $a);
		assertType('bool', $a === [1]);
		assertType('bool', [1, 2] === $a);
		assertType('false', [1, 3] === $a);
		assertType('false', [0] === $a);
		assertType('false', [0, 2] === $a);

		assertType('bool', [1] !== $a);
		assertType('bool', [1, 2] !== $a);
		assertType('true', [1, 3] !== $a);
		assertType('true', [0] !== $a);
		assertType('true', [0, 2] !== $a);

		$b = [1];
		if (doFoo()) {
			$b[] = 2;
		}

		assertType('bool', $a === $b);
		assertType('bool', $a !== $b);
	}

}
