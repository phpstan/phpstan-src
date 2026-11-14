<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15128Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template T of int|string
 */
class A
{

	/**
	 * @return (T is int ? string : int)
	 */
	public function foo(): int|string
	{
		return 1;
	}

}

/**
 * @extends A<string>
 */
class B extends A
{

	public function foo(): int
	{
		return 1;
	}

}

/**
 * @template U of int|string
 * @extends A<U>
 */
class C extends A
{

	public function foo(): int|string
	{
		return 1;
	}

}

interface Foo
{

}

class Base
{

	/**
	 * @return (static is Foo ? int : string)
	 */
	public function bar(): int|string
	{
		return 1;
	}

}

class Child extends Base
{

	public function bar(): int|string
	{
		return 1;
	}

}

class GrandChild extends Child implements Foo
{

}

/**
 * @param C<int> $c
 */
function doFoo(B $b, C $c, Child $child, GrandChild $grandChild): void
{
	assertType('int', $b->foo());
	assertType('string', $c->foo());
	assertType('int|string', $child->bar());
	assertType('int', $grandChild->bar());
}
