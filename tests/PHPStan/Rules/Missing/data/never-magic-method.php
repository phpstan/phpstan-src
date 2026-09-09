<?php // lint >= 8.1

namespace MissingReturnNeverMagicMethod;

class Foo
{

	public function __toString(): never
	{
	}

	public function __clone(): never
	{
	}

	public function __serialize(): never
	{
	}

	public function __isset($name): never
	{
	}

	public function __sleep(): never
	{
		throw new \Exception();
	}

}

class Bar
{

	public function __toString(): string
	{
		return 'foo';
	}

	public function __clone(): void
	{
	}

}
