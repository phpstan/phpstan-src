<?php // lint >= 8.1

namespace NewInInitializers;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template T of object
	 * @param T $test
	 * @return T
	 */
	public function doFoo(
		object $test = new \stdClass()
	): object
	{
		return $test;
	}

	#[\Test(new \stdClass())]
	public function doBar()
	{
		assertType(\stdClass::class, $this->doFoo());
		assertType('$this(NewInInitializers\Foo)', $this->doFoo($this));
		assertType(Bar::class, $this->doFoo(new Bar()));
	}

}

class Bar extends Foo
{

	public function doBar()
	{

	}

	public function doBaz()
	{
		static $o = new \stdClass();
		assertType('mixed', $o);
	}

}
