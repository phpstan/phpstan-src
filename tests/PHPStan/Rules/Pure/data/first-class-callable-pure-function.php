<?php

namespace FirstClassCallablePureFunction;

class Foo
{

	/**
	 * @phpstan-pure
	 */
	function pureFunction()
	{

	}

	/**
	 * @phpstan-impure
	 */
	function impureFunction()
	{
		echo '';
	}

	function voidFunction(): void
	{
		echo 'test';
	}

}

/**
 * @phpstan-pure
 */
function pureFunction()
{

}

/**
 * @phpstan-impure
 */
function impureFunction()
{
	echo '';
}

function voidFunction(): void
{
	echo 'test';
}

/**
 * @phpstan-pure
 */
function testThese(Foo $foo)
{
	$cb = $foo->pureFunction(...);
	$cb();

	$cb = $foo->impureFunction(...);
	$cb();

	$cb = $foo->voidFunction(...);
	$cb();

	$cb = pureFunction(...);
	$cb();

	$cb = impureFunction(...);
	$cb();

	$cb = voidFunction(...);
	$cb();

	callCallbackImmediately($cb);

	$cb = 'FirstClassCallablePureFunction\\pureFunction';
	$cb();

	$cb = 'FirstClassCallablePureFunction\\impureFunction';
	$cb();

	$cb = 'FirstClassCallablePureFunction\\voidFunction';
	$cb();

	$cb = [$foo, 'pureFunction'];
	$cb();

	$cb = [$foo, 'impureFunction'];
	$cb();

	$cb = [$foo, 'voidFunction'];
	$cb();
}

/**
 * @phpstan-pure
 * @return int
 */
function callCallbackImmediately(callable $cb): int
{
	return $cb();
}
