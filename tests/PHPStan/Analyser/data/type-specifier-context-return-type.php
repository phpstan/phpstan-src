<?php

namespace TypeSpecifierContextReturnTypeTest;

use function PHPStan\Testing\assertType;

class ContextReturnType {
	public function returnsInt(int $specifiedContextReturnType): int {}

	public function doFooLeft(int $i) {
		assertType('int', $i);
		if ($this->returnsInt($i) > 0) {
			assertType('int<1, max>', $i);
		} else {
			assertType('int<min, 0>', $i);
		}
		assertType('int', $i);
	}

	public function doFooRight(int $i) {
		assertType('int', $i);
		if (0 < $this->returnsInt($i)) {
			assertType('int<1, max>', $i);
		} else {
			assertType('int<min, 0>', $i);
		}
		assertType('int', $i);
	}
}
