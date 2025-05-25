<?php // lint >= 8.0

namespace Bug8922;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<non-falsy-string> $array
 * @param non-falsy-string $string
 * @param mixed $mixed
 */
function doSomething($array, $string, $mixed): void
{
	assertType('list<non-falsy-string>', mb_detect_order());
	assertType('list<non-falsy-string>', mb_detect_order(null));
	assertType('bool', mb_detect_order($array));
	assertType('bool', mb_detect_order($string));
	assertType('bool|list<non-falsy-string>', mb_detect_order($mixed));
}
