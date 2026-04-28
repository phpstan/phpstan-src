<?php

declare(strict_types=1);

namespace Bug14083;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $convert
 */
function example(array $convert): void {
	foreach ($convert as &$item) {
		$item = strtoupper($item);
	}
	assertType('list<string>', $convert);
}

/**
 * @param list<string> $convert
 */
function example2(array $convert): void {
	foreach ($convert as $key => $item) {
		$convert[$key] = strtoupper($item);
	}
	assertType('list<uppercase-string>', $convert);
}
