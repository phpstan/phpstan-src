<?php declare(strict_types = 1); // lint >= 8.0

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

final class FinalTestPlain
{
	public function doFoo(string $key, string $value): void
	{
		$this[$key] = $value;

		$this = $value;
	}
}

class TestPlain
{
	public function doFoo(string $key, string $value): void
	{
		$this[$key] = $value;

		$this = $value;
	}
}

class TestStatic
{
	static public function doFoo(string $value): void
	{
		$this = $value;
	}
}

function doFoo(string $value): void
{
	$this = $value;
}
