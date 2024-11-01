<?php // lint <= 7.4

namespace CountChars;

use function PHPStan\Testing\assertType;

class X {
	const ABC = 'abcdef';

	function doFoo(): void {
		assertType('array<int, int>|false', count_chars(self::ABC, 0));
		assertType('array<int, int>|false', count_chars(self::ABC, 1));
		assertType('array<int, int>|false', count_chars(self::ABC, 2));

		assertType('string|false', count_chars(self::ABC, 3));
		assertType('string|false', count_chars(self::ABC, 4));

		assertType('string|false', count_chars(self::ABC, -1));
		assertType('string|false', count_chars(self::ABC, 5));
	}
}
