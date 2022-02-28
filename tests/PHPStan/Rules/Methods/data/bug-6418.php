<?php

namespace Bug6418;

class Foo
{

	/**
	 * @template T
	 *
	 * @param T&\DateTimeInterface $p

	 * @return T
	 */
	function test($p) {
		return $p;
	}

}
