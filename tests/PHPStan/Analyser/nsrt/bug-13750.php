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

}

class Analogous
{

	/** @var array<int> */
	private array $prop = [];

	/** @param array<mixed> $arr */
	public function notIdentical(array $arr): void
	{
		if (count($arr) > 2) {
		}
		assertType('int<0, max>', count($arr));
		if ($arr !== []) {
			assertType('int<1, max>', count($arr));
			assertType('int<1, max>', sizeof($arr));
		} else {
			assertType('0', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function truthy(array $arr): void
	{
		if (count($arr) > 2) {
		}
		if ($arr) {
			assertType('int<1, max>', count($arr));
		}
		if (!empty($arr)) {
			assertType('int<1, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function offsetChecks(array $arr): void
	{
		if (count($arr) > 2) {
		}
		if (isset($arr[0])) {
			assertType('int<1, max>', count($arr));
		}
		if (array_key_exists('a', $arr)) {
			assertType('int<1, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function sizeofRecursive(array $arr): void
	{
		if (sizeof($arr) > 2) {
		}
		assertType('int<0, max>', sizeof($arr));
		if (sizeof($arr, COUNT_RECURSIVE) > 2) {
			assertType('int<1, max>', sizeof($arr));
			assertType('int<1, max>', count($arr));
		}
		if (count($arr, COUNT_RECURSIVE) === 0) {
			assertType('0', count($arr));
		}
	}

	public function property(): void
	{
		if (count($this->prop) > 2) {
		}
		assertType('int<0, max>', count($this->prop));
		if ($this->prop !== []) {
			assertType('int<1, max>', count($this->prop));
		}
	}

	public function strlen(string $s): void
	{
		if (strlen($s) > 2) {
		}
		assertType('int<0, max>', strlen($s));
		if ($s !== '') {
			assertType('int<1, max>', strlen($s));
		}
	}

	/** @param array<mixed> $arr */
	public function keepsInformativeMergedCount(array $arr): void
	{
		if (count($arr) === 0) {
			return;
		}
		if (count($arr) > 5) {
		}
		assertType('int<1, max>', count($arr));
		if (count($arr) > 2) {
			assertType('int<3, max>', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function partlyInformativeMergedCount(array $arr): void
	{
		if (count($arr) > 2) {
			if (count($arr) < 5) {
				return;
			}
		}
		assertType('int<0, 2>|int<5, max>', count($arr));
		if ($arr !== []) {
			assertType('int<1, 2>|int<5, max>', count($arr));
		} else {
			assertType('0', count($arr));
		}
	}

	/** @param array<mixed> $arr */
	public function keepsNarrowerCount(array $arr): void
	{
		if (count($arr) > 2) {
			if ($arr !== []) {
				assertType('int<3, max>', count($arr));
			}
		}
	}

}
