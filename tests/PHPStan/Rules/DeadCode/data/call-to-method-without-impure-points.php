<?php

namespace CallToMethodWithoutImpurePoints;

function (): void {
	$x = new finalX();
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
		$xy = new finalX();
	}
	$xy->myFunc();

	$xy = new Y(); // case-insensitive class name
	if (rand(0,1)) {
		$xy = new finalX();
	}
	$xy->myFunc();

	$foo = new Foo();
	$foo->finalFunc();
	$foo->finalThrowingFunc();
	$foo->throwingFunc();

	$subY = new subY();
	$subY->myFunc();
	$subY->myFinalBaseFunc();

	$subSubY = new finalSubSubY();
	$subSubY->myFunc();
	$subSubY->mySubSubFunc();
	$subSubY->myFinalBaseFunc();
};

class y
{
	function myFunc()
	{
	}
	final function myFinalBaseFunc()
	{
	}
}

class subY extends y {
}

final class finalSubSubY extends subY {
	function mySubSubFunc()
	{
	}
}

final class finalX {
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

class foo
{
	final function finalFunc()
	{
	}

	final function finalThrowingFunc()
	{
		throw new \Exception();
	}

	function throwingFunc()
	{
		throw new \Exception();
	}
}

abstract class AbstractFoo
{

	function myFunc()
	{
	}

}
final class FinalFoo extends AbstractFoo
{

}

function (FinalFoo $foo): void {
	$foo->myFunc();
};

class CallsPrivateMethodWithoutImpurePoints
{

	public function doFoo(): void
	{
		$this->doBar();
	}

	private function doBar(): int
	{
		return 1;
	}

}
