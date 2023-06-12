<?php

namespace StubsIntegrationTest\Levels;

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

class YetYetAnotherClassExtendingInterfaceWithStubPhpDoc implements InterfaceWithStubPhpDoc
{

	public function doFoo()
	{
		throw new \Exception();
	}

}

function (YetYetAnotherClassExtendingInterfaceWithStubPhpDoc $stub): int
{
	// return int should be inherited
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

interface InterfaceWithStubPhpDoc2
{

	public function doFoo();

}

function (InterfaceWithStubPhpDoc2 $stub): int
{
	// return int should be inherited
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

class YetYetAnotherClassExtendingInterfaceWithStubPhpDoc2 implements InterfaceWithStubPhpDoc2
{

	public function doFoo()
	{
		throw new \Exception();
	}

}

function (YetYetAnotherClassExtendingInterfaceWithStubPhpDoc2 $stub): int
{
	// return int should be inherited
	$stub->doFoo() === [];
	return $stub->doFoo(); // stub wins
};

class AnotherFooChild extends Foo
{

	public function doFoo($j)
	{
		return '';
	}

}

function (AnotherFooChild $foo): void {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};

class YetAnotherFoo
{

	public function doFoo($j)
	{
		return '';
	}

}

function (YetAnotherFoo $foo): void {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};

class YetYetAnotherFoo
{

	/**
	 * Deliberately wrong phpDoc
	 * @param \stdClass $j
	 * @return \stdClass
	 */
	public function doFoo($j)
	{
		return '';
	}

}

function (YetYetAnotherFoo $foo): void {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};

trait StubbedTrait
{
	public function doFoo($int)
	{

	}
}

class ClassUsingStubbedTrait
{
	use StubbedTrait;
}

function (ClassUsingStubbedTrait $foo): void {
	$foo->doFoo('string');
};
