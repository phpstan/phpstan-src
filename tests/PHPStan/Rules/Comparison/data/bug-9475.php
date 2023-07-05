<?php

namespace Bug9475;

use Stringable;

class Foo
{
	/** @var string */
	private $foo = 'Foo';
	/** @var self */
	protected $bar;

	/** @param mixed[] $array */
	function mustReport(Bug9475 $foo, array $array): void
	{
		echo $this->foo;
		echo $this->$foo;
		echo $this->$array;
		echo $this->$this;
		echo $this->$this->$this;
		echo $this->bar->$foo;
		echo $this->bar->$this;
		echo $this->$bar->$this;
	}
}

class Bar implements Stringable
{
	/** @var string */
	private $foo = 'Foo';
	/** @var self */
	protected $bar;

	function mustReport(Bug9475 $foo): void
	{
		echo $this->foo;
		echo $this->$foo;
		echo $this->$this;
		echo $this->$this->$this;
		echo $this->bar->$foo;
		echo $this->bar->$this;
		echo $this->$bar->$this;
	}

	public function __toString(): string
	{
		return 'foo';
	}
}
