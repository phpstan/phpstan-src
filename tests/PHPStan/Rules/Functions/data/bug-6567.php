<?php declare(strict_types = 1);

namespace Bug6567;

/**
 * @template T of int
 * @param T $p
 * @return T
 */
function add($p) {
	return $p + 1000;
}

/**
 * @param int<0,100> $p
 */
function test($p): void {
	\PHPStan\dumpType(add($p));
}

/**
 * @template T of bool
 * @param T $p
 * @return T
 */
function testBool($p) {
	return false;
}

/**
 * @template T of float
 * @param T $p
 * @return T
 */
function testFloat($p) {
	return $p + 1;
}
