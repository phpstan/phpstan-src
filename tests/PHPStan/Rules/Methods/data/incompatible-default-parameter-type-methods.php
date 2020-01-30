<?php

namespace IncompatibleDefaultParameter;

class FooParent
{

	/**
	 * @param int $int
	 * @param string $string
	 * @param ?float $float
	 * @param float $floatWithIntDefault
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 */
	public function bar(
		$int,
		$string,
		$float,
		$floatWithIntDefault,
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
	 * @param float $floatWithIntDefault
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 */
	public function baz(
		$int = 10,
		$string = 'string',
		$float = null,
		$floatWithIntDefault = 1,
		$object = false,
		$bool = null,
		$resource = false
	): void {
	}

	public function bar(
		$int = 10,
		$string = 'string',
		$float = null,
		$floatWithIntDefault = 1,
		$object = false,
		$bool = null,
		$resource = false
	): void {
	}

}
