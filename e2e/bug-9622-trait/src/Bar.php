<?php

namespace Bug9622Trait;

/**
 * @phpstan-import-type AnArray from Foo
 */
trait Bar
{

	/**
	 * @param AnArray $a
	 */
	public function doFoo(array $a): void
	{
		echo 1 + $a['foo'];
	}

}
