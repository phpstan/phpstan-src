<?php

namespace DuplicateCasesInSwitch;

class Foo
{

	public const EQ = '=';

	public ?int $weightInGrams = null;

	public function doFoo(string $unit): void
	{
		switch (strtolower($unit)) {
			case 'g':
				$this->weightInGrams = 1;
				break;
			case 'kg':
				$this->weightInGrams = 1000;
				break;
			case 'mg':
				$this->weightInGrams = 0;
				break;
			case 'lb':
				$this->weightInGrams = 454;
				break;
			case 'oz':
				$this->weightInGrams = 28;
				break;
			case 'lb':
				$this->weightInGrams = 453;
				break;
			case 'oz':
				$this->weightInGrams = 29;
				break;
		}
	}

	public function intCases(int $i): void
	{
		switch ($i) {
			case 1:
				break;
			case 2:
				break;
			case 1:
				break;
		}
	}

	public function tripleDuplicate(string $s): void
	{
		switch ($s) {
			case 'x':
				break;
			case 'y':
				break;
			case 'x':
				break;
			case 'x':
				break;
		}
	}

	public function classConstant(string $operator): void
	{
		switch ($operator) {
			case '=':
				break;
			case '<':
				break;
			case self::EQ:
				break;
		}
	}

	public function globalConstant(string $s): void
	{
		switch ($s) {
			case 'unknown':
				break;
			case DUPLICATE_SWITCH_CASE_CONST:
				break;
		}
	}

	public function fallthroughGroups(string $s): void
	{
		switch ($s) {
			case 'a':
			case 'b':
				doFoo();
				break;
			case 'a':
				doBar();
				break;
		}
	}

	public function boolAndNullCases(mixed $m): void
	{
		switch ($m) {
			case true:
				break;
			case null:
				break;
			case true:
				break;
			case null:
				break;
		}
	}

	public function defaultIsNotADuplicate(string $s): void
	{
		switch ($s) {
			case 'a':
				break;
			default:
				break;
			case 'b':
				break;
		}
	}

	public function nonConstantConditions(string $s, string $foo): void
	{
		switch ($s) {
			case $foo:
				break;
			case $foo:
				break;
			case rand() === 1 ? 'a' : 'b':
				break;
			case rand() === 1 ? 'a' : 'b':
				break;
		}
	}

	public function looseEqualityIsNotReported(mixed $m): void
	{
		switch ($m) {
			case 1:
				break;
			case '1':
				break;
			case 1.0:
				break;
			case true:
				break;
			case 0:
				break;
			case false:
				break;
		}
	}

	public function separateSwitches(string $s): void
	{
		switch ($s) {
			case 'a':
				break;
		}

		switch ($s) {
			case 'a':
				break;
		}
	}

}
