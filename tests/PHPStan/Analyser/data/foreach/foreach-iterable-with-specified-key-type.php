<?php

namespace ForeachWithGenericsPhpDoc;
class FooIterable
{

	/**
	 * @param iterable<self|Bar, string|int|float> $list
	 */
	public function doFoo(iterable $list)
	{
		foreach ($list as $key => $value) {
			die;
		}
	}

}
