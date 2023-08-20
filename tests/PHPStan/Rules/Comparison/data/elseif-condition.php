<?php

namespace ConstantConditionElseIf;

class ElseIfCondition
{

	/**
	 * @param int $i
	 * @param \stdClass $std
	 * @param Foo|Bar $union
	 * @param Lorem&Ipsum $intersection
	 */
	public function doFoo(int $i, \stdClass $std, $union, $intersection)
	{
		if ($i) {

		} elseif ($std) {

		}

		if ($i) {

		} elseif (!$std) {

		}

		if ($union instanceof Foo || $union instanceof Bar) {

		} elseif ($union instanceof Foo && true) {

		}

		if ($intersection instanceof Lorem && $intersection instanceof Ipsum) {

		} elseif ($intersection instanceof Lorem && true) {

		}
	}

}

class ConditionalAlwaysTrue
{
	/**
	 * @param int $i
	 * @param \stdClass $std
	 */
	public function doFoo(int $i, \stdClass $std)
	{
		if ($i) {
		} elseif ($std) { // always-true should not be reported because last condition
		}

		if ($i) {
		} elseif ($std) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}
	}

}

class ConditionalAlwaysFalse
{
	/**
	 * @param int $i
	 * @param \stdClass $std
	 */
	public function doFoo(int $i)
	{
		$zero = 0;
		if ($i) {
		} elseif ($zero) {
		}

		if ($i) {
		} elseif ($zero) {
		} elseif (rand(0,1)) {
		}
	}

}
