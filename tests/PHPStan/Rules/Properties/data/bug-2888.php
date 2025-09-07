<?php

namespace Bug2888;

class MyClass
{
	/**
	 * @var int[]
	 */
	private $prop = [];

	/**
	 * @return void
	 */
	public function foo()
	{
		array_push($this->prop, 'string');
		array_unshift($this->prop, 'string');
	}

	/**
	 * @return void
	 */
	public function bar()
	{
		$this->prop[] = 'string';
	}
}
