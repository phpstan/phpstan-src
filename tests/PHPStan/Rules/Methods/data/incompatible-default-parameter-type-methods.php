<?php

namespace IncompatibleDefaultParameter;

class FooParent
{

	/**
	 * @template T
	 * @param int $int
	 * @param string $string
	 * @param ?float $float
	 * @param float $floatWithIntDefault
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 * @param T $templateWithIntDefault
	 */
	public function bar(
		$int,
		$string,
		$float,
		$floatWithIntDefault,
		$object,
		$bool,
		$resource,
		$templateWithIntDefault
	): void {
	}

}

class Foo extends FooParent
{

	/**
	 * @template T
	 * @param int $int
	 * @param string $string
	 * @param ?float $float
	 * @param float $floatWithIntDefault
	 * @param \stdClass|false $object
	 * @param bool $bool
	 * @param resource $resource
	 * @param T $templateWithIntDefault
	 */
	public function baz(
		$int = 10,
		$string = 'string',
		$float = null,
		$floatWithIntDefault = 1,
		$object = false,
		$bool = null,
		$resource = false,
		$templateWithIntDefault = 1
	): void {
	}

	public function bar(
		$int = 10,
		$string = 'string',
		$float = null,
		$floatWithIntDefault = 1,
		$object = false,
		$bool = null,
		$resource = false,
		$templateWithIntDefault = 1
	): void {
	}

}
