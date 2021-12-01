<?php

namespace ArrayDestructuring;

class Foo
{

	public function doFoo(?array $arrayOrNull): void
	{
		[$a] = [0, 1, 2];
		[$a] = $arrayOrNull;
		[$a] = [];
		[[$a]] = [new \stdClass()];

		[[$a, $b, $c]] = [[1, 2]];
	}

	public function doBar(): void
	{
		['a' => $a] = ['a' => 1];

		['a' => $a] = ['b' => 1];
	}

	public function doBaz(): void
	{
		$arrayObject = new FooArrayObject();
		['a' => $a] = $arrayObject;
	}

}

class FooArrayObject implements \ArrayAccess
{

	public function offsetGet($key)
	{
		return true;
	}

	public function offsetSet($key, $value): void
	{
	}

	public function offsetUnset($key): void
	{
	}

	public function offsetExists($key): bool
	{
		return false;
	}

}
