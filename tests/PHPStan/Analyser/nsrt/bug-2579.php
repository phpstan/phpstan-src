<?php declare(strict_types = 1);

namespace Bug2579;

use function PHPStan\Testing\assertType;

class A {}
class B {}
class A1 extends A {}
class B1 extends B {}

/**
 * @template T1 of A
 * @template T2 of B
 * @param T1|T2 $type
 * @return T1|T2
 */
function f(object $type) {
	return $type;
}

function test(): void
{
	assertType('Bug2579\\A1', f(new A1()));
	assertType('Bug2579\\B1', f(new B1()));
}

/**
 * @template T1 of A
 * @template T2 of B
 * @template T3 of \Countable
 * @param T1|T2|T3 $type
 * @return T1|T2|T3
 */
function g(object $type) {
	return $type;
}

function test3(): void
{
	assertType('Bug2579\\A1', g(new A1()));
}
