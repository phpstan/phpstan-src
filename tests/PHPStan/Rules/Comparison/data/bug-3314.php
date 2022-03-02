<?php

namespace Bug3314;

class Foo
{

	public function doFoo()
	{
		$values = [];
		while (0 == 1) {
			$values[] = '1';
		}
		$result = in_array('1', $values, true);
	}

}
