<?php

namespace AppendedArrayItemMixed;

class Foo
{

	/** @var int[] */
	private $integers;

	/** @var callable[] */
	private $callables;

	/**
	 * @param mixed $foo
	 */
	public function doFoo($foo, $bar)
	{
		$this->integers[] = $foo;
		$this->integers[] = $bar;
		$this->callables[] = $foo;
		$this->callables[] = $bar;
	}
}
