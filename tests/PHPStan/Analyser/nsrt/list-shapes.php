<?php
namespace ListShapes;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param list{} $l1
	 * @param list{'a'} $l2
	 * @param list{0: 'a'} $l3
	 * @param list{0: 'a', 1: 'b'} $l4
	 * @param list{0: 'a', 1?: 'b'} $l5
	 * @param list{'a', 'b', ...} $l6
	 */
	public function bar($l1, $l2, $l3, $l4, $l5, $l6): void
	{
		assertType('list{}', $l1);
		assertType("list{'a'}", $l2);
		assertType("list{'a'}", $l3);
		assertType("list{'a', 'b'}", $l4);
		assertType("list{0: 'a', 1?: 'b'}", $l5);
		assertType("list{'a', 'b'}", $l6);
	}
}
