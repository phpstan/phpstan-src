<?php // lint < 8.0

namespace Bug13301Php7;

use function PHPStan\Testing\assertType;

function doFoo($mixed) {
	if (array_key_exists('a', $mixed)) {
		assertType("non-empty-array&hasOffset('a')", $mixed);
		echo "has-a";
	} else {
		assertType("mixed~hasOffset('a')", $mixed);
		echo "NO-a";
	}
}
