<?php

namespace UninitializedPropertyReadonlyPhpDoc;

class Foo
{

	/**
	 * @var int
	 * @readonly
	 */
	private $bar;

	public function __construct()
	{

	}

}

class Bar
{

	/**
	 * @var int
	 * @readonly
	 */
	private $bar;

	public function __construct()
	{
		echo $this->bar;
		$this->bar = 1;
	}

}
