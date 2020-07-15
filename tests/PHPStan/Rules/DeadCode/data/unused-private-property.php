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
