<?php // lint >= 8.4

namespace ReturnNullSafeByRefPropertyHools;

use stdClass;

class Foo
{
	public int $i {
		&get {
			$foo = new stdClass();

			return $foo?->foo;
		}
	}
}
