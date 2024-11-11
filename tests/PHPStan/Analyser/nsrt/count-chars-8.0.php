<?php // lint >= 8.0

namespace CountChars;

use function PHPStan\Testing\assertType;

class Y {
	const ABC = 'abcdef';

	function doFoo(): void {
		assertType('array<int, int>', count_chars(self::ABC));
		assertType('array<int, int>', count_chars(self::ABC, 0));
		assertType('array<int, int>', count_chars(self::ABC, 1));
		assertType('array<int, int>', count_chars(self::ABC, 2));

		assertType('string', count_chars(self::ABC, 3));
		assertType('string', count_chars(self::ABC, 4));

		assertType('string', count_chars(self::ABC, -1));
		assertType('string', count_chars(self::ABC, 5));
	}
}
