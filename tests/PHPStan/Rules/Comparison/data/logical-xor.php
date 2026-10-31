<?php

namespace ConstantLogicalXor;

class Foo
{

	/**
	 * @param \stdClass $a
	 * @param \stdClass $b
	 */
	public function doFoo($a, $b)
	{
		if (1 xor 0) {

		}
		if (0 xor 1) {

		}
		if ($a xor $b) {

		}

		1 xor 0;
	}

}
