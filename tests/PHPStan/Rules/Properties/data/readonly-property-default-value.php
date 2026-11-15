<?php // lint >= 8.6

namespace ReadonlyPropertyDefaultValue;

class Foo
{

	public readonly int $prop = 1;

	public readonly ?string $nullable = null;

	public readonly int $noDefault;

	/** @var array<string, int> */
	public readonly array $array = [];

	public function __construct(int $prop, public readonly int $promoted = 1)
	{
		$this->prop = $prop;
		$this->nullable = 'foo';
		$this->noDefault = 1;
		$this->promoted = 2;
		$this->array['foo'] = 1;
	}

	public function setProp(int $prop): void
	{
		$this->prop = $prop;
	}

}

class Child extends Foo
{

	public function __construct()
	{
		parent::__construct(1);
		$this->prop = 2;
	}

}

readonly class ReadonlyClass
{

	public int $prop = 1;

	public function __construct()
	{
		$this->prop = 2;
	}

}

class CloneReinitialization
{

	public readonly int $prop = 1;

	public function __clone()
	{
		$this->prop++;
	}

}

class CloneReinitializationTwice
{

	public readonly int $prop = 1;

	public function __clone()
	{
		$this->prop = 2;
		$this->prop = 3;
	}

}

class CloneWith
{

	public readonly int $prop = 1;

	public function withProp(int $prop): static
	{
		return clone($this, ['prop' => $prop]);
	}

}

class Unserialize
{

	public readonly int $prop = 1;

	/** @param array{prop: int} $data */
	public function __unserialize(array $data): void
	{
		$this->prop = $data['prop'];
	}

}

class ReadInConstructor
{

	public readonly int $prop = 1;

	public function __construct()
	{
		echo $this->prop;
		$this->doFoo();
	}

	private function doFoo(): void
	{
		echo $this->prop;
	}

}
