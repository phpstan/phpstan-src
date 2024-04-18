<?php

namespace CallToMethodWithoutImpurePoints;

function (): void {
	$x = new x();
	$x->myFunc();
	$x->myFUNC();
	$x->throwingFUNC();
	$x->throwingFunc();
	$x->funcWithRef();
	$x->impureFunc();
	$x->callingImpureFunc();

	$a = $x->myFunc();

	$xy = new y();
	if (rand(0,1)) {
		$xy = new x();
	}
	$xy->myFunc();

	$xy = new Y();
	if (rand(0,1)) {
		$xy = new X();
	}
	$xy->myFunc();
};

class y
{
	function myFunc()
	{
	}
}

class x {
	function myFunc()
	{
	}

	function throwingFunc()
	{
		throw new \Exception();
	}

	function funcWithRef(&$a)
	{
	}

	/** @phpstan-impure */
	function impureFunc()
	{
	}

	function callingImpureFunc()
	{
		$this->impureFunc();
	}
}
