<?php

namespace NumberFormatThrowType;

class Foo
{

	/**
	 * @param 2 $phpDocDecimals
	 */
	public function doFoo(float $f, int $decimals, int $phpDocDecimals): void
	{
		try {
			number_format($f);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, 2);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, -2);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, 2147483647);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, 2, ',', ' ');
		} catch (\ValueError $e) {

		}

		try {
			if ($decimals >= 0 && $decimals <= 10) {
				number_format($f, $decimals);
			}
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, 2147483648);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, $decimals);
		} catch (\ValueError $e) {

		}

		try {
			number_format($f, $phpDocDecimals);
		} catch (\ValueError $e) {

		}
	}

}
