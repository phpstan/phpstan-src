<?php

namespace Bug9622;

/**
 * @phpstan-import-type AnArray from Foo
 */
class Bar
{

	/**
	 * @param AnArray $a
	 */
	public function doFoo(array $a): void
	{
		echo 1 + $a['foo'];
	}

}
