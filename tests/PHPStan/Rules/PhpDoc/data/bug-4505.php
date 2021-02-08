<?php

namespace Bug4505;

class Foo
{

	public function doFoo(): void {
		if (true) {
			return;
		}

		/** @var int $foobar */
		$foobar = 1;
	}

}
