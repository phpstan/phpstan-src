<?php

namespace IncompatibleDefaultParameter;

class FooParent
{

	/**
	 * @param int $int
	 * @param string $string
	 * @param ?float $float
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 */
	public function bar(
		$int,
		$string,
		$float,
		$object,
		$bool,
		$resource
	): void {
	}

}

class Foo extends FooParent
{

	/**
	 * @param int $int
	 * @param string $string
	 * @param ?float $float
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 */
	public function baz(
		$int = 10,
		$string = 'string',
		$float = null,
		$object = false,
		$bool = null,
		$resource = false
	): void {
	}

	public function bar(
		$int = 10,
		$string = 'string',
		$float = null,
		$object = false,
		$bool = null,
		$resource = false
	): void {
	}

}

class Bar
{

	/**
	 * @param array{name?:string} $settings
	 */
	public function doFoo(array $settings = []): void
	{

	}

	public function doBar(float $test2 = 0): void
	{

	}

	/**
	 * @template T
	 * @param T $a
	 */
	public function doBaz($a = 'str'): void
	{

	}

}
