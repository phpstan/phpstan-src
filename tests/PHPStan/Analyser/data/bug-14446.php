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

function testMaybeIterable(bool $initial): void {
	$current = $initial;

	while (true) {
		assertType('bool', $initial);
		if (!$current) {
			assertType('bool', $initial);
			break;
		}

		$items = rand() > 0 ? [1] : [];
		foreach ($items as $item) {
			$current = false;
		}
	}

	assertType('bool', $initial);
}

/**
 * @param mixed $value
 */
function testForeachKeyOverwrite($value): void {
	if (is_array($value) && $value !== []) {
		$hasOnlyStringKey = true;
		foreach (array_keys($value) as $key) {
			if (is_int($key)) {
				$hasOnlyStringKey = false;
				break;
			}
		}

		assertType('bool', $hasOnlyStringKey);

		if ($hasOnlyStringKey) {
			// $key should not be in scope here with polluteScopeWithAlwaysIterableForeach: false
			// Second foreach should not report "Foreach overwrites $key with its key variable"
			foreach ($value as $key => $element) {
				assertType('(int|string)', $key);
			}
		}
	}
}
