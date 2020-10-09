<?php

namespace OverridingFinalMethod;

class Foo
{

	final public function doFoo()
	{

	}

	public function doBar()
	{

	}

	public function doBaz()
	{

	}

	protected function doLorem()
	{

	}

	public static function doIpsum()
	{

	}

	public function doDolor()
	{

	}

}

class Bar extends Foo
{

	public function doFoo()
	{

	}

	private function doBar()
	{

	}

	protected function doBaz()
	{

	}

	private function doLorem()
	{

	}

	public function doIpsum()
	{

	}

	public static function doDolor()
	{

	}

}

class Baz
{

	public function __construct(int $i)
	{

	}

}

class Lorem extends Baz
{

	public function __construct(string $s)
	{

	}

}

abstract class Ipsum
{

	abstract public function __construct(int $i);

	public function doFoo(int $i)
	{

	}

}

class Dolor extends Ipsum
{

	public function __construct(string $s)
	{

	}

	public function doFoo()
	{

	}

}

class FixedArray extends \SplFixedArray
{

	public function setSize(int $size): bool
	{

	}

}

class Sit
{

	public function doFoo(int $i, int $j = null)
	{

	}

	public function doBar(int ...$j)
	{

	}

	public function doBaz(int $j)
	{

	}

}

class Amet extends Sit
{

	public function doFoo(int $i = null, int $j = null)
	{

	}

	public function doBar(int $j)
	{

	}

	public function doBaz(int ...$j)
	{

	}

}

class Consecteur extends Sit
{

	public function doFoo(int $i, ?int $j)
	{

	}

}

class Etiam
{

	public function doFoo(int &$i, int $j)
	{

	}

}

class Lacus extends Etiam
{

	public function doFoo(int $i, int &$j)
	{

	}

}

class BazBaz extends Foo
{

	public function doBar(int $i)
	{

	}

}

class BazBazBaz extends Foo
{

	public function doBar(int $i = null)
	{

	}

}

class FooFoo extends Ipsum
{

	public function doFoo(int $i, int $j)
	{

	}

}

class FooFooFoo extends Ipsum
{

	public function doFoo(int $i, int $j = null)
	{

	}

}

/**
 * @implements \IteratorAggregate<int, Foo>
 */
class SomeIterator implements \IteratorAggregate
{
	/**
	 * @return \Traversable<int, Foo>
	 */
	public function getIterator()
	{
		yield new Foo;
	}

}

class SomeException extends \Exception
{

	private function __construct()
	{

	}

}

class OtherException extends \Exception
{

	final public function __construct()
	{

	}

}

class SomeOtherException extends OtherException
{

	public function __construct()
	{

	}

}

class FinalWithAnnotation
{

	/**
	 * @final
	 */
	public function doFoo()
	{

	}

}

class ExtendsFinalWithAnnotation extends FinalWithAnnotation
{

	public function doFoo()
	{

	}

}

class FixedArrayOffsetExists extends \SplFixedArray
{

	public function offsetExists(int $index)
	{

	}

}
