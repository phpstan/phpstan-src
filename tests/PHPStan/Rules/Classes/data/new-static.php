<?php

namespace NewStatic;

class NoConstructor
{

	public function doFoo()
	{
		$foo = new static();
	}

}

class NonFinalConstructor
{

	public function __construct()
	{

	}

	public function doFoo()
	{
		$foo = new static();
	}

}

final class NoConstructorInFinalClass
{

	public function doFoo()
	{
		$foo = new static();
	}

}

final class NonFinalConstructorInFinalClass
{

	public function __construct()
	{

	}

	public function doFoo()
	{
		$foo = new static();
	}

}

class FinalConstructorInNonFinalClass
{

	final public function __construct()
	{

	}

	public function doFoo()
	{
		$foo = new static();
	}

}

interface InterfaceWithConstructor
{

	public function __construct(int $i);

}

class ConstructorComingFromAnInterface implements InterfaceWithConstructor
{

	public function __construct(int $i)
	{

	}

	public function doFoo()
	{
		$foo = new static(1);
	}

}

abstract class AbstractConstructor
{

	abstract public function __construct(string $s);

	public function doFoo()
	{
		new static('foo');
	}

}

class ClassExtendingAbstractConstructor extends AbstractConstructor
{

	public function __construct(string $s)
	{

	}

	public function doBar()
	{
		new static('foo');
	}

}

interface FooInterface
{
	public function __construct();
}

interface BarInterface extends FooInterface {}

class VendorFoo
{
	public function __construct()
	{
	}
}

class Foo extends VendorFoo implements FooInterface
{
	static function build()
	{
		return new static();
	}
}

class Bar extends VendorFoo implements BarInterface
{
	static function build()
	{
		return new static();
	}
}
