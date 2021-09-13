<?php

namespace ArrayFilterCallback;

class Foo
{

	/**
	 * @param int[] $ints
	 * @param mixed[] $mixeds
	 */
	public function doFoo(array $ints, array $mixeds): void
	{
		array_filter($ints, function (int $i): bool {
			return true;
		});
		array_filter($ints, function (string $i): bool {
			return true;
		});
		array_filter($mixeds, function (int $i): bool {
			return true;
		});
	}

}
