<?php declare(strict_types = 1);

namespace Bug15226;

use function PHPStan\Testing\assertType;

/** @param array<mixed> $options */
function test(array $options): void {
    $select_if_unique = isset($options['select']);
    $force = $select_if_unique ? null : ($options['force'] ?? null);
    if (isset($force)) {
	    assertType('false', $select_if_unique); // ok: false
    }
    assertType('bool', $select_if_unique); // should be: bool

}
