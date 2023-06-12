<?php

namespace ConstantCondition\Or;

class BooleanOr
{

	public function doFoo(int $i, bool $j, \stdClass $std, ?\stdClass $nullableStd)
	{
		if ($i || $j) {

		}

		$one = 1;
		if ($one || $i) {

		}

		if ($i || $std) {

		}

		$zero = 0;
		if ($zero || $i) {

		}
		if ($i || $zero) {

		}
		if ($one === 0 || $one) {

		}
		if ($one === 1 || $one) {

		}
		if ($nullableStd || $nullableStd) {

		}
		if ($nullableStd !== null || $nullableStd) {

		}
	}

	/**
	 * @param Foo|Bar $union
	 * @param Lorem&Ipsum $intersection
	 */
	public function checkUnionAndIntersection($union, $intersection)
	{
		if ($union instanceof Foo || $union instanceof Bar) {

		}

		if ($intersection instanceof Lorem || $intersection instanceof Ipsum) {

		}
	}

	public function directorySeparator()
	{
		if (DIRECTORY_SEPARATOR === '/' || DIRECTORY_SEPARATOR === '\\') {

		}

		if ('/' === DIRECTORY_SEPARATOR || '\\' === DIRECTORY_SEPARATOR) {

		}
	}

}

class OrInIfCondition
{
	public function orInIfCondition($mixed, int $i): void
	{
		if (!$mixed) {
			if ($mixed || $i) {
			}
			if ($i || $mixed) {
			}
		}
		if ($mixed) {
			if ($mixed || $i) {
			}
			if ($i || $mixed) {
			}
		}
	}
}

class ConditionalAlwaysTrue
{
	public function doFoo(int $i, $x)
	{
		$one = 1;
		if ($x) {
		} elseif ($one || $i) { // always-true should not be reported because last condition
		}

		if ($x) {
		} elseif ($one || $i) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}

		if ($x) {
		} elseif ($i || $one) { // always-true should not be reported because last condition
		}

		if ($x) {
		} elseif ($i || $one) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}
	}

}
