<?php

namespace ArrayFillThrowType;

class Foo
{

	/**
	 * @param positive-int $phpDocCount
	 */
	public function doFoo(int $start, int $count, int $phpDocCount): void
	{
		try {
			$a = array_fill(0, 3, 'x');
		} catch (\ValueError $e) {

		}

		try {
			$a = array_fill(-5, 3, 'x');
		} catch (\Error $e) {

		}

		try {
			$a = array_fill($start, 0, 'x');
		} catch (\Error $e) {

		}

		try {
			$a = array_fill(0, -1, 'x');
		} catch (\ValueError $e) {

		}

		try {
			$a = array_fill(0, $count, 'x');
		} catch (\ValueError $e) {

		}

		try {
			$a = array_fill(0, $phpDocCount, 'x');
		} catch (\ValueError $e) {

		}

		if ($count >= 0 && $count <= 100) {
			try {
				$a = array_fill(0, $count, 'x');
			} catch (\ValueError $e) {

			}
		}

		try {
			$a = array_fill($start, 3, 'x');
		} catch (\Error $e) {

		}

		try {
			$a = array_fill(PHP_INT_MAX, 2, 'x');
		} catch (\Error $e) {

		}
	}

}
