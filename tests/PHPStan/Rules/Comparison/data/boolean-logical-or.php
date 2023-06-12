<?php

namespace ConstantCondition;

class BooleanOrLogical
{

	public function doFoo(int $i, bool $j, \stdClass $std, ?\stdClass $nullableStd)
	{
		if ($i or $j) {

		}

		$one = 1;
		if ($one or $i) {

		}

		if ($i or $std) {

		}

		$zero = 0;
		if ($zero or $i) {

		}
		if ($i or $zero) {

		}
		if ($one === 0 or $one) {

		}
		if ($one === 1 or $one) {

		}
		if ($nullableStd or $nullableStd) {

		}
		if ($nullableStd !== null or $nullableStd) {

		}
	}

	/**
	 * @param Foo|Bar $union
	 * @param Lorem&Ipsum $intersection
	 */
	public function checkUnionAndIntersection($union, $intersection)
	{
		if ($union instanceof Foo or $union instanceof Bar) {

		}

		if ($intersection instanceof Lorem or $intersection instanceof Ipsum) {

		}
	}

	public function directorySeparator()
	{
		if (DIRECTORY_SEPARATOR === '/' or DIRECTORY_SEPARATOR === '\\') {

		}

		if ('/' === DIRECTORY_SEPARATOR or '\\' === DIRECTORY_SEPARATOR) {

		}
	}

}

class OrInIfConditionLogical
{
	public function orInIfCondition($mixed, int $i): void
	{
		if (!$mixed) {
			if ($mixed or $i) {
			}
			if ($i or $mixed) {
			}
		}
		if ($mixed) {
			if ($mixed or $i) {
			}
			if ($i or $mixed) {
			}
		}
	}
}
