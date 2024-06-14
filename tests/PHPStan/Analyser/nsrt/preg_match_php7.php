<?php // lint < 8.0

namespace PregMatchPhp7;

use function PHPStan\Testing\assertType;

class Foo {
	public function doFoo() {
		assertType('0|1|false', preg_match('{}', ''));
		assertType('int<0, max>|false|null', preg_match_all('{}', ''));
	}
}
