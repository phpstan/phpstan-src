<?php

namespace ForeachWithComplexValueType;

class Fooo
{

	/**
	 * @param iterable<float|self> $list
	 */
	public function doFoo(iterable $list)
	{
		foreach ($list as $value) {
			die;
		}
	}

}
