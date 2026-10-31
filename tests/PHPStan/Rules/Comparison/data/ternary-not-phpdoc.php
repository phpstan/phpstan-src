<?php

namespace ConstantConditionNotPhpDoc;

class Ternary
{

	/**
	 * @param \stdClass $object
	 */
	public function doFoo(
		self $self,
		$object
	): void
	{
		$self ? 'foo' : 'bar';
		$object ? 'foo' : 'bar';
	}

}
