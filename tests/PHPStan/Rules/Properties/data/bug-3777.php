<?php

namespace Bug3777;

use function PHPStan\Analyser\assertType;

class HelloWorld
{
	/**
	 * @var \SplObjectStorage<\DateTimeImmutable, null>
	 */
	public $dates;

	public function __construct()
	{
		$this->dates = new \SplObjectStorage();
		assertType('SplObjectStorage<DateTimeImmutable, null>', $this->dates);
	}
}

/** @template T of object */
class Foo
{

	public function __construct()
	{

	}

}

/** @template T of object */
class Fooo
{

}

class Bar
{

	/** @var Foo<\stdClass> */
	private $foo;

	/** @var Fooo<\stdClass> */
	private $fooo;

	public function __construct()
	{
		$this->foo = new Foo();
		assertType('Bug3777\Foo<stdClass>', $this->foo);

		$this->fooo = new Fooo();
		assertType('Bug3777\Fooo<stdClass>', $this->fooo);
	}

	public function doBar()
	{
		$this->foo = new Fooo();
		assertType('Bug3777\Fooo<object>', $this->foo);
	}

}

/**
 * @template T of object
 * @template U of object
 */
class Lorem
{

	/**
	 * @param T $t
	 * @param U $u
	 */
	public function __construct($t, $u)
	{

	}

}

class Ipsum
{

	/** @var Lorem<\stdClass, \Exception> */
	private $lorem;

	/** @var Lorem<\stdClass, \Exception> */
	private $ipsum;

	public function __construct()
	{
		$this->lorem = new Lorem(new \stdClass, new \Exception());
		assertType('Bug3777\Lorem<stdClass, Exception>', $this->lorem);
		$this->ipsum = new Lorem(new \Exception(), new \stdClass);
		assertType('Bug3777\Lorem<Exception, stdClass>', $this->ipsum);
	}

}

/**
 * @template T of object
 * @template U of object
 */
class Lorem2
{

	/**
	 * @param T $t
	 */
	public function __construct($t)
	{

	}

}

class Ipsum2
{

	/** @var Lorem2<\stdClass, \Exception> */
	private $lorem2;

	/** @var Lorem2<\stdClass, \Exception> */
	private $ipsum2;

	public function __construct()
	{
		$this->lorem2 = new Lorem2(new \stdClass);
		assertType('Bug3777\Lorem2<stdClass, object>', $this->lorem2);
		$this->ipsum2 = new Lorem2(new \Exception());
		assertType('Bug3777\Lorem2<Exception, object>', $this->ipsum2);
	}

}
