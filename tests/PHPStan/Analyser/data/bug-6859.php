<?php declare(strict_types = 1);

namespace Bug6859;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function f($body)
	{
		if (array_key_exists("someParam", $body)) {
			assertType('array{someParam: mixed}', array_keys($body));

			$someKeys = array_filter(
				array_keys($body),
				fn ($key) => preg_match("/^somePattern[0-9]+$/", $key)
			);

			assertType('array', $someKeys);

			if (count($someKeys) > 0) {
				return 1;
			}
			return 0;
		}
	}
}
