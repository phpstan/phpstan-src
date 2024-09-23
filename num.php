<?php

use function PHPStan\Testing\assertType;

/**
 * @param array<int, string> $arr
 */
function narrowKey($mixed, string $s, int $i, array $generalArr, array $arr): void {
	if (isset($arr[$s])) {
		assertType('numeric-string', $s);
	} else {
		assertType('string', $s);
	}
	assertType('string', $s);
}
