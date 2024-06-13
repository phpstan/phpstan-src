<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID >= 80000

namespace ArrayChunkPhp8;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param int<-5, -10> $negativeRange
	 * @param int<-5, 0> $negativeWithZero
	 */
	public function negativeLength(array $arr, $negativeRange, $negativeWithZero) {
		assertType('*NEVER*', array_chunk($arr, $negativeRange));
		assertType('*NEVER*', array_chunk($arr, $negativeWithZero));
	}

}
