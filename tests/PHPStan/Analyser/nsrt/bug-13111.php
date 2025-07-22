<?php declare(strict_types = 1);

namespace Bug13111;

use function PHPStan\Testing\assertType;

/** @return array{0?: string, 1?: int} */
function foo() { return [1 => 8]; }

$b = foo();
if (count($b) === 1) {
	assertType('non-empty-array{0?: string, 1?: int}', $b);
}
