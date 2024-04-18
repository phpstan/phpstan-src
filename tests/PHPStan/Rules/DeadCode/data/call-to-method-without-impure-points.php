<?php

namespace CallToMethodWithoutImpurePoints;

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
};
