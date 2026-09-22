<?php

namespace RoundThrowType;

class Foo
{

	/**
	 * @param 1 $phpDocMode
	 */
	public function doFoo(float $f, int $mode, int $phpDocMode): void
	{
		try {
			$a = round($f);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 2);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, PHP_ROUND_HALF_EVEN);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, 8);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, \RoundingMode::HalfEven);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, 9);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, $mode);
		} catch (\ValueError $e) {

		}

		try {
			$a = round($f, 0, $phpDocMode);
		} catch (\ValueError $e) {

		}
	}

}
