<?php

namespace PregMatch;

use function PHPStan\Testing\assertType;

class Foo {
	public function doFoo() {
		assertType('0|1', preg_match('{}', ''));
		assertType('int<0, max>|null', preg_match_all('{}', ''));
	}
}
