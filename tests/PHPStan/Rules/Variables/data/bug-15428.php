<?php declare(strict_types = 1);

namespace Bug15428Variables;

/**
 * @param array{a?: string, ...} $c
 */
function repro(array &$c): void
{
	$x = $c['a'] ?? ''; // this causes $c to lose it's type
	$c['a'] = '1';
}
