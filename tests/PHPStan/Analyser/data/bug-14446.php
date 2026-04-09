<?php declare(strict_types = 1);

namespace Bug14446;

use function PHPStan\Testing\assertType;

function test(bool $initial): void {
    $current = $initial;

    while (true) {
		assertType('bool', $initial);
        if (!$current) {
			assertType('bool', $initial);
            break;
        }

        $items = [1];
        foreach ($items as $item) {
            $current = false;
        }
    }

	assertType('bool', $initial);
}
