<?php declare(strict_types = 1);

namespace Bug15284;

use function PHPStan\Testing\assertType;

/**
 * @template T = static
 */
class CoffeeBreak
{
}

/**
 * @template T = $this
 */
class CoffeeBreakThis
{
}

/**
 * @template T = list<static>|null
 */
class CoffeeBreakList
{
}

/**
 * @template T of object = static
 */
class CoffeeBreakBoundAndDefault
{
}

/**
 * @template T of list<static>
 */
class CoffeeBreakListBound
{

	/** @param T $t */
	public function __construct($t)
	{
	}

}

/**
 * @template T of static|null
 */
class CoffeeBreakBound
{

	/** @param T $t */
	public function __construct($t)
	{
	}

}

/**
 * @template T = static
 */
class CoffeeBreakGetter
{

	/** @return T */
	public function get()
	{
		throw new \Exception();
	}

}

class CoffeeBreakChild extends CoffeeBreakGetter
{
}

function (): void {
	$cb = new CoffeeBreak();
	assertType('Bug15284\CoffeeBreak<Bug15284\CoffeeBreak>', $cb);

	$cb = new CoffeeBreakThis();
	assertType('Bug15284\CoffeeBreakThis<Bug15284\CoffeeBreakThis>', $cb);

	$cb = new CoffeeBreakList();
	assertType('Bug15284\CoffeeBreakList<list<Bug15284\CoffeeBreakList>|null>', $cb);

	$cb = new CoffeeBreakBoundAndDefault();
	assertType('Bug15284\CoffeeBreakBoundAndDefault<Bug15284\CoffeeBreakBoundAndDefault>', $cb);

	$cb = new CoffeeBreakListBound([]);
	assertType('Bug15284\CoffeeBreakListBound<array{}>', $cb);

	$cb = new CoffeeBreakBound(null);
	assertType('Bug15284\CoffeeBreakBound<null>', $cb);

	$cb = new CoffeeBreakGetter();
	assertType('Bug15284\CoffeeBreakGetter<Bug15284\CoffeeBreakGetter>', $cb);
	assertType('Bug15284\CoffeeBreakGetter', $cb->get());

	$child = new CoffeeBreakChild();
	assertType('Bug15284\CoffeeBreakChild', $child);
	assertType('Bug15284\CoffeeBreakGetter', $child->get());
};

/**
 * @param CoffeeBreakListBound<list<CoffeeBreakListBound<array{}>>> $a
 * @param CoffeeBreakBound<CoffeeBreakBound<null>> $b
 */
function doFoo(CoffeeBreakListBound $a, CoffeeBreakBound $b): void
{
	$c = $a;
	assertType('Bug15284\CoffeeBreakListBound<list<Bug15284\CoffeeBreakListBound<array{}>>>', $c);
	$d = $b;
	assertType('Bug15284\CoffeeBreakBound<Bug15284\CoffeeBreakBound<null>>', $d);
}
