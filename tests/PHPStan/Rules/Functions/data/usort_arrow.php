<?php declare(strict_types = 1); // lint >= 7.4

namespace UsortCallbackArrow;

class Foo
{

	public function doFoo(): void
	{
		$array = [1,2,3];

		usort(
			$array,
			fn (string $one, string $two) => 1
		);
	}

}
