<?php declare(strict_types = 1);

namespace UsortCallback;

class Foo
{

	public function doFoo(): void
	{
		$array = [1,2,3];

		usort(
			$array,
			function (string $one, string $two) {
				return 1;
			}
		);
	}

}
