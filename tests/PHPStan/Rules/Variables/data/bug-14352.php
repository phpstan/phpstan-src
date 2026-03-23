<?php declare(strict_types = 1);

namespace Bug14352;

use ArrayAccess;

class TestArrayAccess implements ArrayAccess
{
	public function doFoo(string $key, string $value): void
	{
		$this[$key] = $value; // fine because ArrayAccess

		$this = $value; // should still fail
	}

	public function offsetExists(mixed $offset): bool
	{
	}

	public function offsetGet(mixed $offset): mixed
	{
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
	}

	public function offsetUnset(mixed $offset): void
	{
	}
}

class TestArrayAccessReassign implements ArrayAccess
{
	public function doFoo(self $other): void
	{
		$this = $other;
	}

	public function offsetExists(mixed $offset): bool
	{
	}

	public function offsetGet(mixed $offset): mixed
	{
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
	}

	public function offsetUnset(mixed $offset): void
	{
	}
}

final class FinalTestPlain
{
	public function doFoo(string $key, string $value): void
	{
		$this[$key] = $value;
	}
}

class TestPlain
{
	public function doFoo(string $key, string $value): void
	{
		$this[$key] = $value;
	}
}
