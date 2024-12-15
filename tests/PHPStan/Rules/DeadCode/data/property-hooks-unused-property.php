<?php // lint >= 8.4

namespace PropertyHooksUnusedProperty;

class FooUsed
{

	private int $a {
		get {
			return $this->a + 100;
		}
		set {
			$this->a = $value - 100;
		}
	}

	public function setA(int $a): void
	{
		$this->a = $a;
	}

	public function getA(): int
	{
		return $this->a;
	}

}

class FooUnused
{

	private int $a {
		get {
			return $this->a + 100;
		}
		set {
			$this->a = $value - 100;
		}
	}

}

class FooOnlyRead
{

	private int $a {
		get {
			return $this->a + 100;
		}
		set {
			$this->a = $value - 100;
		}
	}

	public function getA(): int
	{
		return $this->a;
	}

}

class FooOnlyWritten
{

	private int $a {
		get {
			return $this->a + 100;
		}
		set {
			$this->a = $value - 100;
		}
	}

	public function setA(int $a): void
	{
		$this->a = $a;
	}

}

class ReadInAnotherPropertyHook
{
	public function __construct(
		private readonly string $bar,
	) {}

	public string $virtualProperty {
		get => $this->bar;
	}
}

class ReadInAnotherPropertyHook2
{

	private string $bar;

	public string $virtualProperty {
		get => $this->bar;
	}
}

class WrittenInAnotherPropertyHook
{

	private string $bar;

	public string $virtualProperty {
		set {
			$this->bar = 'test';
		}
	}
}
