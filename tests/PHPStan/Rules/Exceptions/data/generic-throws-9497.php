<?php declare(strict_types = 1);

namespace GenericThrows9497;

use RangeException;
use RuntimeException;

class Test
{

	/**
	 * @throws RangeException
	 */
	public function sayHello(): never
	{
		$this->throwIt(new RangeException);
	}

	/**
	 * @param T $e
	 *
	 * @template T of RuntimeException
	 *
	 * @throws T
	 */
	public function throwIt(RuntimeException $e): never
	{
		throw $e;
	}

}

class TestClassString
{

	/**
	 * @throws RangeException
	 */
	public function sayHello(): never
	{
		$this->throwIt(RangeException::class);
	}

	/**
	 * @param class-string<T> $e
	 *
	 * @template T of RuntimeException
	 *
	 * @throws T
	 */
	public function throwIt(string $e): never
	{
		throw new $e;
	}

}
