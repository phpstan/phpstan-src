<?php

// This class and namespace intentionally conflict with the ones from
// foreach-with-specified-key-type.php to test that they can co-exist in one pass.
namespace ForeachWithComplexValueType;

class Foo
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
