<?php

namespace RangeThrowType;

class Foo
{

	public function doFoo(int $i): void
	{
		if (PHP_VERSION_ID < 80000) {
			// PHP 7 reported an invalid step with a warning instead of a ValueError
			try {
				$a = range(1, 10, 0);
			} catch (\ValueError $e) {

			}

			return;
		}

		try {
			$a = range(1, 10);
		} catch (\ValueError $e) {

		}

		try {
			$a = range(0, 1000);
		} catch (\ValueError $e) {

		}

		try {
			$a = range(10, 1, -1);
		} catch (\ValueError $e) {

		}

		try {
			$a = range(1, 10, 0);
		} catch (\ValueError $e) {

		}

		try {
			$a = range(1, $i);
		} catch (\ValueError $e) {

		}

		try {
			$a = range(0, 2000000000);
		} catch (\ValueError $e) {

		}

		if (PHP_VERSION_ID >= 80300) {
			try {
				$a = range('a', 'z');
			} catch (\ValueError $e) {

			}

			// PHP 8.3 rejects a negative step on an increasing range
			try {
				$a = range(1, 1000, -1);
			} catch (\ValueError $e) {

			}

			// PHP 8.3 builds a character range, in which 2 exceeds the range from '9' to ':'
			try {
				$a = range('9', ':', 2);
			} catch (\ValueError $e) {

			}

			// PHP 8.3 compares an integral float step exactly, and 2 ** 60 exceeds the range up to 2 ** 60 - 1
			try {
				$a = range(0, 1152921504606846975, 1152921504606846976.0);
			} catch (\ValueError $e) {

			}
		} else {
			// the sign of the step used to be ignored
			try {
				$a = range(1, 1000, -1);
			} catch (\ValueError $e) {

			}

			// '1' used to be a number next to a non-numeric string, so the step exceeds the range 1..0
			try {
				$a = range('1', 'a', 30);
			} catch (\ValueError $e) {

			}

			// integers are compared exactly, and 2 ** 60 exceeds the range up to 2 ** 60 - 1
			try {
				$a = range(0, 1152921504606846975, 1152921504606846976);
			} catch (\ValueError $e) {

			}

			// the number of items does not fit into a float, which exceeds every array size
			try {
				$a = range(-1.0e308, 1.0e308);
			} catch (\ValueError $e) {

			}

			try {
				$a = range(0, 1, 1.0e-320);
			} catch (\ValueError $e) {

			}
		}
	}

}
