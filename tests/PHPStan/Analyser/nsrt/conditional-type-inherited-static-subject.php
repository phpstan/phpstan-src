<?php declare(strict_types = 1);

namespace ConditionalTypeInheritedStaticSubject;

use function PHPStan\Testing\assertType;

interface Marker
{

}

class A
{

	/**
	 * @return (static is Marker ? int : string)
	 */
	public function foo()
	{
		throw new \Exception();
	}

}

class B extends A implements Marker
{

	public function foo()
	{
		throw new \Exception();
	}

}

class C extends B
{

	public function foo()
	{
		throw new \Exception();
	}

}

class NotMarked extends A
{

	public function foo()
	{
		throw new \Exception();
	}

}

class MarkedLater extends NotMarked implements Marker
{

}

function test(A $a, B $b, C $c, NotMarked $notMarked, MarkedLater $markedLater): void
{
	assertType('int|string', $a->foo());
	assertType('int', $b->foo());
	assertType('int', $c->foo());
	assertType('int|string', $notMarked->foo());
	assertType('int', $markedLater->foo());
}
