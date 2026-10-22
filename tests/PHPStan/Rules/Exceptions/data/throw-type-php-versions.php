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

	public function doFilterVar(string $address): void
	{
		try {
			$a = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);
		} catch (\Filter\FilterFailedException $e) {

		}
	}

	public function doFilterVarThrowOnFailure(string $address): void
	{
		try {
			$a = filter_var($address, FILTER_VALIDATE_IP, FILTER_THROW_ON_FAILURE);
		} catch (\Filter\FilterFailedException $e) {

		}
	}

}
