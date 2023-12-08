<?php

namespace Bug10264;

use function PHPStan\Testing\assertType;

class A
{
	function doFoo() {
		/** @var list<A> $list */
		$list = [];

		assertType('list<Bug10264\A>', $list);

		assert((count($list) <= 1) === true);
		assertType('list<Bug10264\A>', $list);
	}
}


