<?php

namespace Bug8924;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $array
 */
function foo(array $array): void {
	foreach ($array as $element) {
		assertType('int', $element);
		$array = null;
	}
}
