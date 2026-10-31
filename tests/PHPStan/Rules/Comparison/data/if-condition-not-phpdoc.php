<?php

namespace ConstantConditionNotPhpDoc;

class IfCondition
{

	/**
	 * @param \stdClass $object
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
