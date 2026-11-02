<?php // lint >= 8.0

namespace OffsetAccessValueAssignmentAppend;

/** @implements \ArrayAccess<int|null, int|string> */
final class Storage implements \ArrayAccess
{

	public function offsetExists(mixed $offset): bool
	{
		return true;
	}

	public function offsetGet(mixed $offset): mixed
	{
		return 1;
	}

	/**
	 * @param int|null $offset
	 * @param ($offset is null ? int : string) $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
	}

	public function offsetUnset(mixed $offset): void
	{
	}

}

function doFoo(Storage $storage): void
{
	$storage[] = 'str';
	$storage[] = 1;
	$storage[1] = 'str';
	$storage[1] = 1;
}
