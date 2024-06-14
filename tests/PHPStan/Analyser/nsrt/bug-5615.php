<?php

namespace Bug5615;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$cb = function (bool &$save): void {

		};
		$save = true;
		$cb($save);
		assertType('mixed', $save);
	}

	/**
	 * @param callable(bool &$save): void $call
	 */
	function get(callable $call) {
		$save = true;
		$call($save);
		assertType('mixed', $save);
	}

}
