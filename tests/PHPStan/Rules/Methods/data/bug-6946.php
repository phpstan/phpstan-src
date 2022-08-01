<?php declare(strict_types = 1);

namespace Bug6964;

abstract class A {}

class B extends A {}
class C extends A {}

/**
 * @template T of A
 */
abstract class Foo
{
	/**
	 * @param T $a
	 */
	public function apply(A $a): void
	{
	}
}

/**
 * @extends Foo<B>
 */
class Bar extends Foo {}

/**
 * @extends Foo<C>
 */
class Baz extends Foo {}

function test(bool $barOrBaz): void
{
	if ($barOrBaz) {
		$inner = new B();
		$upper = new Bar();
	} else {
		$inner = new C();
		$upper = new Baz();
	}

	$upper->apply($inner);
}
