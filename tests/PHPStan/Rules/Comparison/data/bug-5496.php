<?php

namespace Bug5496;

class ConstParamTypes
{
	/**
	 * @param 'none'|array<string, 'auto'|'copy'> $propagation
	 */
	public function propagate($propagation): void
	{
	}
}

class Foo
{

	public function doFoo()
	{
		$type = new ConstParamTypes();

		/** @var array<string, 'auto'|'copy'> $propagation */
		$propagation = [];

		if (\in_array('auto', $propagation, true)) {
			$type->propagate($propagation);
		}

		$type->propagate(['yakdam' => 'copy']);
	}

}
