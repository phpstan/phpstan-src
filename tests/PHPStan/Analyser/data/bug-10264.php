<?php

namespace Bug10264;

use function PHPStan\Testing\assertType;
use stdClass;

class A
{
	function doFoo() {
		/** @var list<A> $list */
		$list = [];

		assertType('list<Bug10264\A>', $list);

		assert((count($list) <= 1) === true);
		assertType('list<Bug10264\A>', $list);
	}

	/** @param list<int> $c */
	public function sayHello(array $c): void
	{
		assertType('list<int>', $c);
		if (count($c) > 0) {
			$c = array_map(fn () => new stdClass(), $c);
			assertType('non-empty-list<stdClass>', $c);
		} else {
			assertType('array{}', $c);
		}

		assertType('list<stdClass>', $c);
	}
}
