<?php declare(strict_types = 1);

namespace OffsetAccessValueAssignmentCoalesce;

/**
 * @implements \ArrayAccess<string, int>
 */
class Foo implements \ArrayAccess
{
	public function offsetExists($offset): bool
	{
		return true;
	}

	public function offsetGet($offset): ?string
	{
		return 'x';
	}

	/**
	 * @param string|null $offset
	 * @param int $value
	 */
	public function offsetSet($offset, $value): void
	{
	}

	public function offsetUnset($offset): void
	{
	}
}

function coalesceValid(Foo $foo): void
{
	$foo['x'] ??= 5;
}

function coalesceInvalid(Foo $foo): void
{
	$foo['x'] ??= 'boom';
}

function ifValid(Foo $foo): void
{
	if (null === $foo['x']) {
		$foo['x'] = 5;
	}
}

function ifInvalid(Foo $foo): void
{
	if (null === $foo['x']) {
		$foo['x'] = 'boom';
	}
}
