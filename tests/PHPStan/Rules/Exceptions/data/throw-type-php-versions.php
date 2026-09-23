<?php

namespace ThrowTypePhpVersions;

class Foo
{

	public function doRound(float $f, int $mode): void
	{
		if (PHP_VERSION_ID >= 80400) {
			try {
				$a = round($f, 0, $mode);
			} catch (\ValueError $e) {

			}
		} else {
			try {
				$a = round($f, 0, $mode);
			} catch (\ValueError $e) {

			}
		}

		try {
			$a = round($f, 0, $mode);
		} catch (\ValueError $e) {

		}
	}

	public function doStrSplit(string $s, int $length): void
	{
		if (PHP_VERSION_ID >= 80000) {
			try {
				$a = str_split($s, $length);
			} catch (\ValueError $e) {

			}
		} else {
			try {
				$a = str_split($s, $length);
			} catch (\ValueError $e) {

			}
		}

		try {
			$a = str_split($s, $length);
		} catch (\ValueError $e) {

		}
	}

}
