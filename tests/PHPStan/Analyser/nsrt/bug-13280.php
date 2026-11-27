<?php declare(strict_types = 1);

namespace Bug13280;

use function PHPStan\Testing\assertType;

/** @param array{int,int}|array{'auto',int}|array{int,'auto'} $size */
function getSize(array $size) : int {
	assertType("array{'auto'|int, int}|array{int, 'auto'}", $size);
	if ($size[0] === 'auto') {
		assertType("array{'auto', int}", $size);
		return 1*$size[1];
	} elseif ($size[1] === 'auto') {
		assertType("array{int, 'auto'}", $size);
		return $size[0]*1;
	} else {
		assertType('array{int, int}', $size);
		return $size[0]*$size[1];
	}
}
