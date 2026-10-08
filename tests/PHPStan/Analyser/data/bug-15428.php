<?php declare(strict_types = 1);

namespace Bug15428Data;

use function PHPStan\Testing\assertType;

/**
 * @param array{a?: string, ...} $c
 */
function unsealed(array $c): void
{
	$x = $c['a'] ?? '';
	assertType('array{a?: string, ...}', $c);
}

/**
 * @param array{a?: string} $c
 */
function sealed(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{}', $c);
	}
}
