<?php

namespace Bug9475;

use Stringable;

class Foo
{
	/** @var string */
	private $foo = 'Foo';

	/** @param mixed[] $array */
	function mustReport(Bug9475 $foo, array $array): void
	{
		echo $this->foo;
		echo $this->$foo;
		echo $this->$array;
		echo $this->$this;
	}
}

class Bar implements Stringable
{
	/** @var string */
	private $foo = 'Foo';

	function mustReport(Bug9475 $foo): void
	{
		echo $this->foo;
		echo $this->$foo;
		echo $this->$this;
	}

	public function __toString(): string
	{
		return 'foo';
	}
}
