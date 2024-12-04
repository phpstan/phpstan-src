<?php

namespace CountType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-array $nonEmpty
	 */
	public function doFoo(
		array $nonEmpty
	)
	{
		assertType('int<1, max>', count($nonEmpty));
		assertType('int<1, max>', sizeof($nonEmpty));
	}

	/**
	 * @param int<3,5> $range
	 * @param int<0,5> $maybeZero
	 * @param int<-10,-5> $negative
	 */
	public function doFooBar(
		array $arr,
		int $range,
		int $maybeZero,
		int $negative
	)
	{
		if (count($arr) == $range) {
			assertType('non-empty-array', $arr);
		} else {
			assertType('array', $arr);
		}
		if (count($arr) === $range) {
			assertType('non-empty-array', $arr);
		} else {
			assertType('array', $arr);
		}

		if (count($arr) == $maybeZero) {
			assertType('array', $arr);
		} else {
			assertType('array', $arr);
		}
		if (count($arr) === $maybeZero) {
			assertType('array', $arr);
		} else {
			assertType('array', $arr);
		}

		if (count($arr) == $negative) {
			assertType('*NEVER*', $arr);
		} else {
			assertType('array', $arr);
		}
		if (count($arr) === $negative) {
			assertType('*NEVER*', $arr);
		} else {
			assertType('array', $arr);
		}
	}

	/** @param array{0: string, 1?: string} $arr */
	public function doBar(array $arr): void
	{
		if (count($arr) <= 1) {
			assertType('1', count($arr));
			return;
		}

		assertType('2', count($arr));
		assertType('array{string, string}', $arr);
	}

	/** @param array{0: string, 1?: string} $arr */
	public function doBaz(array $arr): void
	{
		if (count($arr) > 1) {
			assertType('2', count($arr));
			assertType('array{string, string}', $arr);
		}

		assertType('1|2', count($arr));
	}

}

/**
 * @param \ArrayObject<int, mixed> $obj
 */
function(\ArrayObject $obj): void {
	if (count($obj) === 0) {
		assertType('ArrayObject', $obj);
		return;
	}

	assertType('ArrayObject', $obj);
};

function($mixed): void {
	if (count($mixed) === 0) {
		assertType('array{}|Countable', $mixed);
		return;
	}

	assertType('mixed~array{}', $mixed);
};
