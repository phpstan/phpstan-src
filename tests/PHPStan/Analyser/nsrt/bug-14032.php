<?php declare(strict_types=1);

namespace Bug14032;

use function PHPStan\Testing\assertType;

/**
 * Minimal reproducer for type loss in arrays with 62+ keys.
 *
 * Run with: php bin/phpstan analyze reproducer.php
 *
 * Before fix: dumpType shows bool|float|int|string (union of ALL value types)
 * After fix: dumpType shows float (the correct specific type)
 *
 * @param array{
 *     a1?: string, a2?: int, a3?: float, a4?: bool,
 *     b1?: string, b2?: int, b3?: float, b4?: bool,
 *     c1?: string, c2?: int, c3?: float, c4?: bool,
 *     d1?: string, d2?: int, d3?: float, d4?: bool,
 *     e1?: string, e2?: int, e3?: float, e4?: bool,
 *     f1?: string, f2?: int, f3?: float, f4?: bool,
 *     g1?: string, g2?: int, g3?: float, g4?: bool,
 *     h1?: string, h2?: int, h3?: float, h4?: bool,
 *     i1?: string, i2?: int, i3?: float, i4?: bool,
 *     j1?: string, j2?: int, j3?: float, j4?: bool,
 *     k1?: string, k2?: int, k3?: float, k4?: bool,
 *     l1?: string, l2?: int, l3?: float, l4?: bool,
 *     m1?: string, m2?: int, m3?: float, m4?: bool,
 *     n1?: string, n2?: int, n3?: float, n4?: bool,
 *     o1?: string, o2?: int, o3?: float, o4?: bool,
 *     p1?: string, p2?: int, p3?: float, p4?: bool,
 *     target?: float
 * } $data Array with 65 optional keys
 */
function test(array $data): void
{
	if (array_key_exists('target', $data)) {
		// First time it works, this shows float
		assertType('float', $data['target']);
	}

	if (array_key_exists('target', $data)) {
		// Second time it doesn't work
		// This shows: bool|float|int|string
		assertType('float', $data['target']);
	}
}
