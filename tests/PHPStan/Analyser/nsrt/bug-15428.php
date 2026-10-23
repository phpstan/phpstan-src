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
function issetBranches(array $c): void
{
	if (isset($c['a'])) {
		assertType('array{a: string, ...}', $c);
	} else {
		assertType('array{a?: never, ...}', $c);
	}
	assertType('array{a?: string, ...}', $c);
}

/**
 * @param array{a?: string, ...} $c
 */
function arrayKeyExistsBranches(array $c): void
{
	if (array_key_exists('a', $c)) {
		assertType('array{a: string, ...}', $c);
	} else {
		assertType('array{a?: never, ...}', $c);
	}
	assertType('array{a?: string, ...}', $c);
}

/**
 * @param array{a?: string, b: int, ...<string, int>} $c
 */
function typedExtras(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{a?: never, b: int, ...<string, int>}', $c);
	}
	assertType('array{a?: string, b: int, ...<string, int>}', $c);
}

/**
 * @param array{b: int, ...<string, int>} $c
 */
function implicitKey(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{b: int, ...<string, int>}', $c);
	}
	assertType('array{b: int, ...<string, int>}', $c);
}

/**
 * @param array{a?: string, ...<int, int>} $c
 */
function extrasCannotHoldKey(array $c): void
{
	if (!isset($c['a'])) {
		assertType('array{...<int, int>}', $c);
	}
	assertType('array{a?: string, ...<int, int>}', $c);
}
