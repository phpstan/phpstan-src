<?php

namespace Bug6654;

use function PHPStan\Testing\assertType;

class Foo {
	function doFoo() {
		$data = '';
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
		assertType('string',json_encode($data, $flags));

		if (rand(0, 1)) {
			$flags |= JSON_FORCE_OBJECT;
		}

		assertType('string', json_encode($data, $flags));
	}
}
