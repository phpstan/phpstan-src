<?php declare(strict_types = 1);

namespace Bug12565;

class EntryType {
	public string $title = '';
	public string $subTitle = '';
}
/**
 * @implements \ArrayAccess<int, EntryType>
 */
class ArrayLike implements \ArrayAccess {

	/** @var EntryType[] */
	private array $values = [];
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->values[$offset]);
	}

	public function offsetGet(mixed $offset): EntryType
	{
		return $this->values[$offset] ?? new EntryType();
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->values[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->values[$offset]);
	}
}

class Wrapper {
	public ?ArrayLike $myArrayLike;

	public function __construct()
	{
		$this->myArrayLike = new ArrayLike();

	}
}

$baz = new Wrapper();
$baz->myArrayLike = new ArrayLike();
$baz->myArrayLike[1] = new EntryType();
$baz->myArrayLike[1]->title = "Test";
