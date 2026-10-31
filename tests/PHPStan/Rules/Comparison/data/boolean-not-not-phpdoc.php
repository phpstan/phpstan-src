<?php

namespace ConstantConditionNotPhpDoc;

class BooleanNot
{

	/**
	 * @param \stdClass $object
	 */
	public function doFoo(
		self $self,
		$object
	)
	{
		if (!$self) {

		}

		if (!$object) {

		}
	}

}
