<?php // lint >= 8.0

namespace Bug14573;

class Foo
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): self
	{
		return new self($this->test);
	}

}

class Bar
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self($this->a, $this->b);
	}

	public function getA(): int
	{
		return $this->a;
	}

}

class Baz
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self($this->a, $this->b);
	}

}

class Quux
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self($this->b, $this->a);
	}

}

class WithAdditionalRead
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): self
	{
		return new self($this->test);
	}

	public function getTest(): int
	{
		return $this->test;
	}

}

class WithNewStatic
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): static
	{
		return new static($this->test);
	}

}

class WithNewClassName
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): self
	{
		return new WithNewClassName($this->test);
	}

}

class DifferentPropertyPassedToSameParam
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self($this->b, $this->a);
	}

	public function getA(): int
	{
		return $this->a;
	}

}

class WithNamedArgument
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self(b: $this->b, a: $this->a);
	}

}

class WithNamedArgumentSwapped
{

	public function __construct(private int $a, private int $b)
	{
	}

	public function doFoo(): self
	{
		return new self(b: $this->a, a: $this->b);
	}

}

class PassedToOtherClassConstructor
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): Foo
	{
		return new Foo($this->test);
	}

}

class MultipleReadsOnlySelfWrite
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): self
	{
		return new self($this->test);
	}

	public function doBar(): self
	{
		return new self($this->test);
	}

}

class MixedSelfWriteAndTrueRead
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(): self
	{
		return new self($this->test);
	}

	public function getTest(): int
	{
		return $this->test;
	}

}

class SelfWriteInNonConstructorCall
{

	public function __construct(private int $test)
	{
	}

	public function doFoo(int $test): self
	{
		$this->doBar($this->test);
		return new self($this->test);
	}

	private function doBar(int $test): void
	{
	}

}
