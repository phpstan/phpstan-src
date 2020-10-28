<?php // lint >= 8.0

namespace UninitializedProperty;

class Foo
{

	private int $x;

	public function __construct(
		private int $y
	)
	{
		$this->x = $this->y;
	}

}

class Bar
{

	public function __construct(
		private int $x
	)
	{

	}

}

class Baz
{

	public function __construct(
		private int $x
	)
	{
		 assert($this->x >= 0.0);
	}

}
