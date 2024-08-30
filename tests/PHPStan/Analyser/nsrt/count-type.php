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

}
