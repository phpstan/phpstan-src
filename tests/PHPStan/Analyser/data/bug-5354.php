<?php

namespace Bug5354;

use function PHPStan\Testing\assertType;

/**
 * @param mixed[] $foo
 */
function sayHello(array $foo): void
{
	$a = [];
	foreach ($foo as $e) {
		$a[] = rand(5, 15) > 10 ? 0 : 1;
	}

	assertType('bool', \in_array(0, $a, true));
}
