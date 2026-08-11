<?php

namespace MethodNever;

class Foo
{

	public function doFoo(): never
	{
		throw new \Exception();
	}

	/**
	 * @return never
	 */
	public function doFoo2()
	{
		throw new \Exception();
	}

	public function doBar(): void
	{
		throw new \Exception();
	}

	public function callsNever()
	{
		$this->doFoo();
	}

	public function doBaz()
	{
		while (true) {

		}
	}

	public function onlySometimes()
	{
		if (rand(0, 1)) {
			return;
		}

		throw new \Exception();
	}

	/**
	 * @return \Generator<int, int, null, never>
	 */
	public function yields(): \Generator
	{
		while(true) {
			yield 1;
		}
	}

}

class MagicMethods
{

	public function __construct()
	{
		throw new \Exception();
	}

	public function __destruct()
	{
		throw new \Exception();
	}

	public function __clone(): never
	{
		throw new \Exception();
	}

	public function __toString(): never
	{
		throw new \Exception();
	}

	public function __isset($name): never
	{
		throw new \Exception();
	}

	public function __set($name, $value): never
	{
		throw new \Exception();
	}

	public function __unset($name): never
	{
		throw new \Exception();
	}

	public function __sleep(): never
	{
		throw new \Exception();
	}

	public function __wakeup(): never
	{
		throw new \Exception();
	}

	public function __serialize(): never
	{
		throw new \Exception();
	}

	public function __unserialize(array $data): never
	{
		throw new \Exception();
	}

	public static function __set_state($properties): never
	{
		throw new \Exception();
	}

	public function __debugInfo(): never
	{
		throw new \Exception();
	}

}

class MagicMethodsWithoutNever
{

	public function __clone(): void
	{
		throw new \Exception();
	}

	public function __toString(): string
	{
		throw new \Exception();
	}

}
