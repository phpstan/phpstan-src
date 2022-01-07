<?php // lint >= 7.4

namespace UnusedPrivateProperty;

class Foo
{

	private $foo;

	private $bar; // write-only

	private $baz; // unused

	private $lorem; // read-only

	private $ipsum;

	private $dolor = 0;

	public function __construct()
	{
		$this->foo = 1;
		$this->bar = 2;
		$this->ipsum['foo']['bar'] = 3;
		$this->dolor++;
	}

	public function getFoo()
	{
		return $this->foo;
	}

	public function getLorem()
	{
		return $this->lorem;
	}

	public function getIpsum()
	{
		return $this->ipsum;
	}

	public function getDolor(): int
	{
		return $this->dolor;
	}

}

class Bar
{

	private int $foo;

	private int $bar; // do not report read-only, it's uninitialized

	private $baz; // report read-only

	public function __construct()
	{
		$this->foo = 1;
	}

	public function getFoo(): int
	{
		return $this->foo;
	}

	public function getBar(): int
	{
		return $this->bar;
	}

	public function getBaz(): int
	{
		return $this->baz;
	}

}

class Baz
{

	private static $foo;

	private static $bar; // write-only

	private static $baz; // unused

	private static $lorem; // read-only

	public static function doFoo()
	{
		self::$foo = 1;
		self::$bar = 2;
	}

	public static function getFoo()
	{
		return self::$foo;
	}

	public static function getLorem()
	{
		return self::$lorem;
	}

}

class Lorem
{

	private $foo = 'foo';

	private $bar = 'bar';

	private $baz = 'baz';

	public function doFoo()
	{
		$nameProperties = [
			'foo',
			'bar',
		];

		foreach ($nameProperties as $nameProperty) {
			echo "Hello, {$this->$nameProperty}";
		}
	}

}

class Ipsum
{

	private $foo = 'foo';

	public function doBar(string $s)
	{
		echo $this->{$s};
	}

}

class DolorWithAnonymous
{

	private $foo;

	public function doFoo()
	{
		new class () {
			private $bar;
		};
	}

}

class ArrayAssign
{

	private $foo;

	public function doFoo(): void
	{
		[$this->foo] = [1];
	}

}

class ArrayAssignAndRead
{

	private $foo;

	public function doFoo(): void
	{
		[$this->foo] = [1];
	}

	public function getFoo()
	{
		return $this->foo;
	}

}

class ListAssign
{

	private $foo;

	public function doFoo(): void
	{
		list($this->foo) = [1];
	}

}

class ListAssignAndRead
{

	private $foo;

	public function doFoo(): void
	{
		list($this->foo) = [1];
	}

	public function getFoo()
	{
		return $this->foo;
	}

}
