<?php

namespace Bug5860;

class Foo
{

	/**
	 * @template T
	 * @param T $t
	 * @return T
	 */
	function test($t) {
		if ($t === null) {
			return $t;
		}
		return $t;
	}

}
