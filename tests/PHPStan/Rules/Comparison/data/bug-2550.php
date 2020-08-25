<?php

namespace Bug2550;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFoo()
	{
		$apples = [1, 'a'];

		foreach($apples as $apple) {
			if (is_numeric($apple)) {
				assertType('1', $apple);
			}
		}
	}

}
