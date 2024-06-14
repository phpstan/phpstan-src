<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug6859;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function keys($body)
	{
		if (array_key_exists("someParam", $body)) {
			assertType('non-empty-list<(int|string)>', array_keys($body));

			$someKeys = array_filter(
				array_keys($body),
				fn ($key) => preg_match("/^somePattern[0-9]+$/", $key)
			);

			assertType('array<int<0, max>, (int|string)>', $someKeys);

			if (count($someKeys) > 0) {
				return 1;
			}
			return 0;
		}
	}

	public function values($body)
	{
		if (array_key_exists("someParam", $body)) {
			assertType('non-empty-list<mixed>', array_values($body));
		}
	}
}
