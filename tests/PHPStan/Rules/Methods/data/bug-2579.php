<?php declare(strict_types = 1);

namespace Bug2579Methods;

class A {}
class B {}
class A1 extends A {
	public function foo(): void {}
}
class B1 extends B {
	public function bar(): void {}
}

/**
 * @template T1 of A
 * @template T2 of B
 * @param T1|T2 $type
 * @return T1|T2
 */
function f(object $type)
{
	return new $type();
}

function test(): void
{
	(f(new A1()))->foo(); // should pass
	(f(new B1()))->bar(); // should pass

	(f(new A1()))->bar(); // should fail
	(f(new B1()))->foo(); // should fail
}
