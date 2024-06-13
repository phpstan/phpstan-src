<?php // onlyif PHP_VERSION_ID < 80000

namespace PregMatchPhp7;

use function PHPStan\Testing\assertType;

class Foo {
	public function doFoo() {
		assertType('0|1|false', preg_match('{}', ''));
		assertType('int<0, max>|false|null', preg_match_all('{}', ''));
	}
}
