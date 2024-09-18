<?php

namespace Bug3777Static;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var \SplObjectStorage<\DateTimeImmutable, null>
	 */
	public static $dates;

	public function __construct()
	{
		static::$dates = new \SplObjectStorage();
		assertType('SplObjectStorage<DateTimeImmutable, null>', static::$dates);
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
	private static $foo;

	/** @var Fooo<\stdClass> */
	private static $fooo;

	public function __construct()
	{
		static::$foo = new Foo();
		assertType('Bug3777Static\Foo<stdClass>', static::$foo);

		static::$fooo = new Fooo();
		assertType('Bug3777Static\Fooo<stdClass>', static::$fooo);
	}

	public function doBar()
	{
		static::$foo = new Fooo();
		assertType('Bug3777Static\Fooo<object>', static::$foo);
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
	private static $lorem;

	/** @var Lorem<\stdClass, \Exception> */
	private static $ipsum;

	public function __construct()
	{
		static::$lorem = new Lorem(new \stdClass, new \Exception());
		assertType('Bug3777Static\Lorem<stdClass, Exception>', static::$lorem);
		static::$ipsum = new Lorem(new \Exception(), new \stdClass);
		assertType('Bug3777Static\Lorem<Exception, stdClass>', static::$ipsum);
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
	private static $lorem2;

	/** @var Lorem2<\stdClass, \Exception> */
	private static $ipsum2;

	public function __construct()
	{
		static::$lorem2 = new Lorem2(new \stdClass);
		assertType('Bug3777Static\Lorem2<stdClass, object>', static::$lorem2);
		static::$ipsum2 = new Lorem2(new \Exception());
		assertType('Bug3777Static\Lorem2<Exception, object>', static::$ipsum2);
	}

}

/**
 * @template T of object
 * @template U of object
 */
class Lorem3
{

	/**
	 * @param T $t
	 * @param U $u
	 */
	public function __construct($t, $u)
	{

	}

}

class Ipsum3
{

	/** @var Lorem3<\stdClass, \Exception> */
	private static $lorem3;

	/** @var Lorem3<\stdClass, \Exception> */
	private static $ipsum3;

	public function __construct()
	{
		static::$lorem3 = new Lorem3(new \stdClass, new \Exception());
		assertType('Bug3777Static\Lorem3<stdClass, Exception>', static::$lorem3);
		static::$ipsum3 = new Lorem3(new \Exception(), new \stdClass());
		assertType('Bug3777Static\Lorem3<Exception, stdClass>', static::$ipsum3);
	}

}
