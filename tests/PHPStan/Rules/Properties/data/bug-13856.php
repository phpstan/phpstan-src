<?php // lint >= 8.1

namespace Bug13856;

/** @implements \ArrayAccess<object, bool> */
class CustomArrayAccess implements \ArrayAccess
{

	public function offsetExists($offset): bool
	{
		return true;
	}

	public function offsetGet($offset): mixed
	{
		return true;
	}

	public function offsetSet($offset, $value): void
	{
	}

	public function offsetUnset($offset): void
	{
	}

}

class Foo
{

	/** @var \SplObjectStorage<object, bool> */
	private readonly \SplObjectStorage $store;

	private readonly CustomArrayAccess $custom;

	public function __construct()
	{
		$this->store = new \SplObjectStorage();
		$this->store[(object) ['foo' => 'bar']] = true;

		$this->custom = new CustomArrayAccess();
		$this->custom[(object) ['foo' => 'bar']] = true;
	}

}

class ReadonlyArray
{

	/** @var array<int, int> */
	private readonly array $numbers;

	public function __construct()
	{
		$this->numbers = [];
		$this->numbers[] = 1;
	}

}

class ExistingOffset
{

	/** @var \ArrayObject<string, int> */
	private readonly \ArrayObject $storage;

	public function __construct()
	{
		$this->storage = new \ArrayObject();
		$this->storage['a'] = 1;
		$this->storage['a'] = 2;
		unset($this->storage['a']);
	}

}
