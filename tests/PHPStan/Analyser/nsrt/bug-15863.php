<?php declare(strict_types = 1);

namespace Bug15863;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, array{'A1', 'A2'}> $groups
 * @param 0|1 $k
 */
function unsetFromOneShape(array $groups, string $key, int $k): void
{
	unset($groups[$key][$k]);
	assertType("array<string, array{0?: 'A1', 1?: 'A2'}>", $groups);
}

/**
 * @param array<string, array{'A1', 'A2'}|array{'B1', 'B2', 'B3'}> $groups
 * @param 0|1|2 $k
 */
function unsetFromShapes(array $groups, string $key, int $k): void
{
	unset($groups[$key][$k]);
	assertType("array<string, array{0?: 'A1'|'B1', 1?: 'A2'|'B2', 2?: 'B3'}>", $groups);
}
