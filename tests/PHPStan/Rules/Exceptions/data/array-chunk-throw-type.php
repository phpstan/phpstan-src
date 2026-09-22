<?php

namespace ArrayChunkThrowType;

class Foo
{

	/**
	 * @param positive-int $phpDocLength
	 */
	public function doFoo(array $a, int $length, int $phpDocLength): void
	{
		try {
			$b = array_chunk($a, 3);
		} catch (\ValueError $e) {

		}

		try {
			$b = array_chunk($a, 2, true);
		} catch (\ValueError $e) {

		}

		try {
			$b = array_chunk($a, 0);
		} catch (\ValueError $e) {

		}

		try {
			$b = array_chunk($a, $length);
		} catch (\ValueError $e) {

		}

		try {
			$b = array_chunk($a, $phpDocLength);
		} catch (\ValueError $e) {

		}

		if ($length > 0) {
			try {
				$b = array_chunk($a, $length);
			} catch (\ValueError $e) {

			}
		}
	}

}
