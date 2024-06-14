<?php

namespace Bug9734;

use function array_is_list;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<mixed> $a
	 * @return void
	 */
	public function doFoo(array $a): void
	{
		if (array_is_list($a)) {
			assertType('list<mixed>', $a);
		} else {
			assertType('array', $a); // could be non-empty-array
		}
	}

	public function doFoo2(): void
	{
		$a = [];
		if (array_is_list($a)) {
			assertType('array{}', $a);
		} else {
			assertType('*NEVER*', $a);
		}
	}

	/**
	 * @param non-empty-array<mixed> $a
	 * @return void
	 */
	public function doFoo3(array $a): void
	{
		if (array_is_list($a)) {
			assertType('non-empty-list<mixed>', $a);
		} else {
			assertType('non-empty-array', $a);
		}
	}

}
