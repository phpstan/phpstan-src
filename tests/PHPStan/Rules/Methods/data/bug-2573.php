<?php

namespace Bug2573;

class Foo
{

	/**
	 * @template T1
	 * @template T2
	 * @param T1 $a
	 * @param T2 $b
	 * @return T1|T2
	 */
	function chooseOne($a, $b = []) {
		return rand(0, 1) ? $a : $b;
	}

}
