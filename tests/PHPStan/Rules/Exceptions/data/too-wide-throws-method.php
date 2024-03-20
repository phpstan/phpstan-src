<?php

namespace TooWideThrowsMethod;

use DomainException;

class Foo
{

	/** @throws \InvalidArgumentException */
	public function doFoo(): void // ok
	{
		throw new \InvalidArgumentException();
	}

	/** @throws \LogicException */
	public function doFoo2(): void // ok
	{
		throw new \InvalidArgumentException();
	}

	/** @throws \InvalidArgumentException */
	public function doFoo3(): void // ok
	{
		throw new \LogicException();
	}

	/** @throws \InvalidArgumentException|\DomainException */
	public function doFoo4(): void // error - DomainException unused
	{
		throw new \InvalidArgumentException();
	}

	/** @throws void */
	public function doFoo5(): void // ok - picked up by different rule
	{
		throw new \InvalidArgumentException();
	}

	/** @throws \InvalidArgumentException|\DomainException */
	public function doFoo6(): void // ok
	{
		if (rand(0, 1)) {
			throw new \InvalidArgumentException();
		}

		throw new DomainException();
	}

	/** @throws \DomainException */
	public function doFoo7(): void // error - DomainException unused
	{
		throw new \InvalidArgumentException();
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \DomainException
	 */
	public function doFoo8(): void // error - DomainException unused
	{
		throw new \InvalidArgumentException();
	}

	/** @throws \DomainException */
	public function doFoo9(): void // error - DomainException unused
	{

	}

}

class ParentClass
{

	/** @throws \LogicException */
	public function doFoo(): void
	{

	}

}

class ChildClass extends ParentClass
{

	public function doFoo(): void
	{

	}

}

class ThrowsReflectionException
{

	/** @throws \ReflectionException */
	public function doFoo(string $s): void
	{
		new \ReflectionClass($s);
	}

}

class SkipThrowable
{

	/**
	 * @throws \InvalidArgumentException
	 * @throws \DomainException
	 */
	public function doFoo(\Throwable $t)
	{
		if (rand(0, 1)) {
			throw new \InvalidArgumentException();
		}

		throw $t;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doBar(): void
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\Throwable $e) {
			throw $e;
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doBaz(): void
	{
		try {
			if (rand(0, 1)) {
				throw new \InvalidArgumentException();
			}

			doFoo();
		} catch (\Throwable $e) {
			throw $e;
		}
	}

}

class ImmediatelyCalledCallback
{

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo(array $a): void
	{
		array_map(function () {
			throw new \InvalidArgumentException();
		}, $a);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo2(array $a): void
	{
		$cb = function () {
			throw new \InvalidArgumentException();
		};
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo3(array $a): void
	{
		$f = function () {
			throw new \InvalidArgumentException();
		};
		$f();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo4(array $a): void
	{
		(function () {
			throw new \InvalidArgumentException();
		})();
	}

}
