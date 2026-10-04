<?php // lint >= 8.1

namespace NeverMagicMethodReturnType;

class Foo
{

	public function __clone(): never
	{
		return;
	}

	public function __serialize(): never
	{
		return [];
	}

	public function __toString(): never
	{
		return 'foo';
	}

	public function __isset($name): never
	{
		return true;
	}

	public function __sleep(): never
	{
		if (rand(0, 1)) {
			return ['a'];
		}

		throw new \Exception();
	}

	public function __clone2(): never
	{
		throw new \Exception();
	}

}

class Bar
{

	public function __clone(): void
	{
		return;
	}

	public function __toString(): string
	{
		return 'foo';
	}

}
