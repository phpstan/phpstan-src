<?php

namespace Bug2782;

class Foo
{

	public function doFoo(): void
	{
		$arr = [new \stdClass, new \stdClass];
		usort(
			$arr,
			function (int $i, int $j) : int {
				return $i > $j ? 1 : -1;
			}
		);
	}

}
