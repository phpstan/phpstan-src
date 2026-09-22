<?php

namespace StrSplitThrowType;

class Foo
{

	/**
	 * @param positive-int $phpDocLength
	 */
	public function doFoo(string $s, int $length, int $phpDocLength): void
	{
		try {
			$a = str_split($s);
		} catch (\ValueError $e) {

		}

		try {
			$a = str_split($s, 3);
		} catch (\ValueError $e) {

		}

		try {
			$a = str_split($s, 0);
		} catch (\ValueError $e) {

		}

		try {
			$a = str_split($s, $length);
		} catch (\ValueError $e) {

		}

		try {
			$a = str_split($s, $phpDocLength);
		} catch (\ValueError $e) {

		}

		if ($length > 0) {
			try {
				$a = str_split($s, $length);
			} catch (\ValueError $e) {

			}
		}
	}

}
