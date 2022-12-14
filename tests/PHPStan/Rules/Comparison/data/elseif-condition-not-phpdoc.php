<?php

namespace ConstantConditionNotPhpDoc;

class ElseIfCondition
{

	/**
	 * @param object $object
	 */
	public function doFoo(
		self $self,
		$object
	): void
	{
		if (rand(0, 1)) {

		} elseif ($self) {

		}

		if (rand(0, 1)) {

		} elseif ($object) {

		}
	}

}

class ConditionalAlwaysTrue
{
	/**
	 * @param object $object
	 */
	public function doFoo(
		self $self,
			 $object
	): void
	{
		if (rand(0, 1)) {
		} elseif ($self) { // always-true should not be reported because last condition
		}

		if (rand(0, 1)) {
		} elseif ($self) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}


		if (rand(0, 1)) {
		} elseif ($object) { // always-true should not be reported because last condition
		}

		if (rand(0, 1)) {
		} elseif ($object) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}

	}

}

