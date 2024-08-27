<?php // lint >= 8.0

namespace TooWidePropertyType;

class Foo
{

	/** @var int|string */
	private $foo;

	/** @var int|null */
	private $bar; // do not report "null" as not assigned

	/** @var int|null */
	private $barr = 1; // report "null" as not assigned

	/** @var int|null */
	private $barrr; // assigned in constructor - report "null" as not assigned

	private int|null $baz; // report "null" as not assigned

	public function __construct()
	{
		$this->barrr = 1;
	}

	public function doFoo(): void
	{
		$this->foo = 1;
		$this->bar = 1;
		$this->barr = 1;
		$this->barrr = 1;
		$this->baz = 1;
	}

}

class Bar
{

	private ?int $a = null;

	private ?int $b = 1;

	private ?int $c = null;

	private ?int $d = 1;

	public function doFoo(): void
	{
		$this->a = 1;
		$this->b = null;
	}

}

class Baz
{

	private ?int $a = null;

	public function doFoo(): self
	{
		$s = new self();
		$s->a = 1;

		return $s;
	}

}

class Lorem
{

	public function __construct(
		private ?int $a = null
	)
	{

	}

	public function doFoo(): void
	{
		$this->a = 1;
	}

}
