<?php

namespace OffsetValueAfterAssign;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string> $a
	 */
	public function doFoo(array $a, int $i): void
	{
		assertType('string', $a[$i]);

		$a[$i] = 'foo';
		assertType('\'foo\'', $a[$i]);

		$i = 1;
		assertType('string', $a[$i]);
	}

	/**
	 * @param \ArrayAccess<int, string> $a
	 */
	public function doBar(\ArrayAccess $a, int $i): void
	{
		assertType('string|null', $a[$i]);

		$a[$i] = 'foo';
		assertType('\'foo\'', $a[$i]);
		assertType('ArrayAccess<int, string>', $a);

		$i = 1;
		assertType('string|null', $a[$i]);
		assertType('ArrayAccess<int, string>', $a);
	}

}
