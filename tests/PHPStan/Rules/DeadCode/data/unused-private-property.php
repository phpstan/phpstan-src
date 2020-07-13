<?php

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
