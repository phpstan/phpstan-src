<?php // onlyif PHP_VERSION_ID >= 80300

namespace JsonValidate;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s): void
	{
		if (json_validate($s)) {
			assertType('non-empty-string', $s);
		} else {
			assertType('string', $s);
		}
	}

}
