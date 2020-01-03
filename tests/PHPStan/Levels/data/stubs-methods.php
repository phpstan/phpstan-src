<?php

namespace StubsIntegrationTest;

class Foo
{

	public function doFoo($i)
	{
		return '';
	}

}

function (Foo $foo) {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};

class FooChild extends Foo
{

	public function doFoo($i)
	{
		return '';
	}

}

function (FooChild $fooChild) {
	$string = $fooChild->doFoo('test');
	$fooChild->doFoo($string);
};

interface InterfaceWithStubPhpDoc
{

	/**
	 * @return string
	 */
	public function doFoo();

}

function (InterfaceWithStubPhpDoc $stub): int
{
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

interface InterfaceExtendingInterfaceWithStubPhpDoc extends InterfaceWithStubPhpDoc
{

}

function (InterfaceExtendingInterfaceWithStubPhpDoc $stub): int
{
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

interface AnotherInterfaceExtendingInterfaceWithStubPhpDoc extends InterfaceWithStubPhpDoc
{

	/**
	 * @return string
	 */
	public function doFoo();

}

function (AnotherInterfaceExtendingInterfaceWithStubPhpDoc $stub): int
{
	return $stub->doFoo(); // implementation wins - string -> int mismatch reported
};

class ClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

	public function doFoo()
	{
		throw new \Exception();
	}

}

function (ClassExtendingInterfaceWithStubPhpDoc $stub): int
{
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

class AnotherClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

	/**
	 * @return string
	 */
	public function doFoo()
	{
		throw new \Exception();
	}

}

function (AnotherClassExtendingInterfaceWithStubPhpDoc $stub): int
{
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

/** This one is missing in the stubs */
class YetAnotherClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

	/**
	 * @return string
	 */
	public function doFoo()
	{
		throw new \Exception();
	}

}

function (YetAnotherClassExtendingInterfaceWithStubPhpDoc $stub): int
{
	return $stub->doFoo(); // implementation wins - string -> int mismatch reported
};
