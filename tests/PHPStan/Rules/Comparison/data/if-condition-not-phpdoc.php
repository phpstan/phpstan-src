<?php

namespace ConstantConditionNotPhpDoc;

class IfCondition
{

	/**
	 * @param object $object
	 */
	public function doFoo(
		self $self,
		$object
	): void
	{
		if ($self) {

		}

		if ($object) {

		}
	}

}
