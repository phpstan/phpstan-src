<?php declare(strict_types = 1);

namespace Bug15428;

use function PHPStan\Testing\assertType;

/**
 * @param array{a?: string, ...} $c
 */
function repro(array &$c): void
{
	$x = $c['a'] ?? ''; // this causes $c to lose it's type
	assertType('array{a?: string, ...}', $c);
	$c['a'] = '1';
}

/**
 * @param array{a?: string, ...} $c
 */
function ifIsset(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{a?: string, ...}', $c);
	}
	assertType('array{a?: string, ...}', $c);
}

/**
 * @param array{a?: string, ...<string, int>} $c
 */
function typedUnsealed(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{a?: string, ...<string, int>}', $c);
	}
	assertType('array{a?: string, ...<string, int>}', $c);
}

/**
 * @param array{a?: string} $c
 */
function sealed(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{}', $c);
	}
	assertType('array{}|array{a: string}', $c);
}
