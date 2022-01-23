<?php // lint >= 8.1

namespace FirstClassCallableFunctionWithoutSideEffect;

class Foo
{

	public static function doFoo(): void
	{
		$f = mkdir(...);

		mkdir(...);
	}

}

class Bar
{

	public static function doFoo(): void
	{
		$f = strlen(...);

		strlen(...);
	}

}

function foo(): never
{
	throw new \Exception();
}

function (): void {
	$f = foo(...);
	foo(...);
};

/**
 * @throws \Exception
 */
function bar()
{
	throw new \Exception();
}

function (): void {
	$f = bar(...);
	bar(...);
};
