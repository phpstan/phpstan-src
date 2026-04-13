<?php

namespace Bug13750;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array<mixed> $arr */
	public function forgetCount(array $arr): void
	{
		if (count($arr) > 2) {
			assertType('non-empty-array<mixed>', $arr);
			assertType('int<3, max>', count($arr));
		}
		assertType('array<mixed>', $arr);
		assertType('int<0, max>', count($arr));
		if (count($arr, COUNT_RECURSIVE) > 2) {
			assertType('non-empty-array<mixed>', $arr);
			assertType('int<1, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function forgetCountDifferentNarrowing(array $arr): void
	{
		if (count($arr) > 2) {
		}
		if ($arr !== []) {
			assertType('int<1, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function sizeofAfterSizeof(array $arr): void
	{
		if (sizeof($arr) > 2) {
		}
		if ($arr !== []) {
			assertType('int<1, max>', sizeof($arr));
		}
	}

	public function strlenAfterStrlen(string $str): void
	{
		if (strlen($str) > 5) {
		}
		if ($str !== '') {
			assertType('int<1, max>', strlen($str));
		}
	}
}
