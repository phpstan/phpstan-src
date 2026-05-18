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
	public function sizeofAfterCount(array $arr): void
	{
		if (sizeof($arr) > 2) {
			assertType('int<3, max>', sizeof($arr));
		}
		assertType('int<0, max>', sizeof($arr));
		if (sizeof($arr, COUNT_RECURSIVE) > 2) {
			assertType('non-empty-array<mixed>', $arr);
			assertType('int<1, max>', sizeof($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function countAfterNonEmptyNarrowing(array $arr): void
	{
		if (count($arr) > 2) {
			assertType('int<3, max>', count($arr));
		}
		assertType('int<0, max>', count($arr));
		if ($arr !== []) {
			assertType('non-empty-array<mixed>', $arr);
			assertType('int<1, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function nestedCountPreservation(array $arr): void
	{
		if (count($arr) > 2) {
			assertType('int<3, max>', count($arr));
			if (count($arr, COUNT_RECURSIVE) > 10) {
				assertType('int<3, max>', count($arr));
			}
		}
	}

	public function strlenAfterNonEmptyNarrowing(string $str): void
	{
		if (strlen($str) > 5) {
			assertType('int<6, max>', strlen($str));
		}
		assertType('int<0, max>', strlen($str));
		if ($str !== '') {
			assertType('non-empty-string', $str);
			assertType('int<1, max>', strlen($str));
		}
	}

	public function mbStrlenAfterNonEmptyNarrowing(string $str): void
	{
		if (mb_strlen($str) > 5) {
			assertType('int<6, max>', mb_strlen($str));
		}
		assertType('int<0, max>', mb_strlen($str));
		if ($str !== '') {
			assertType('non-empty-string', $str);
			assertType('int<1, max>', mb_strlen($str));
		}
	}
}
