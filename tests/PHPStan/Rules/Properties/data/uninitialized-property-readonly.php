<?php // lint >= 8.1

namespace UninitializedPropertyReadonly;

class Foo
{

	private readonly int $bar;

	public function __construct()
	{

	}

}

class Bar
{

	private readonly int $bar;

	public function __construct()
	{
		echo $this->bar;
		$this->bar = 1;
	}

}
