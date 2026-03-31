<?php

declare(strict_types = 1);

namespace Bug10085;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $a
 * @param list<string> $b
 */
function foo(array $a, array $b): void {
	$foo = [...$a, ...$b];
	assertType('list<string>', $foo);
}
