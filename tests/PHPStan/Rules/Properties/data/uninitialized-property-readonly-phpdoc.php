<?php // lint >= 7.4

namespace UninitializedPropertyReadonlyPhpDoc;

class Foo
{

	/** @readonly */
	private int $bar;

	public function __construct()
	{

	}

}

class Bar
{

	/** @readonly */
	private int $bar;

	public function __construct()
	{
		echo $this->bar;
		$this->bar = 1;
	}

}

/** @phpstan-immutable */
class Immutable
{

	private int $bar;

	public function __construct()
	{

	}

}

/** @immutable */
class A
{

	public string $a;

}

class B extends A
{

	public string $b;

}

class C extends B
{

	public string $c;

}
