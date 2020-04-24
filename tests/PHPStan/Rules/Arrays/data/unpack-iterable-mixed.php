<?php // lint >= 7.4

namespace UnpackIterable;

class Foo
{

	/**
	 * @param mixed $explicitMixed
	 */
	public function doFoo(
		$explicitMixed,
		$mixed
	)
	{
		$foo = [
			...$explicitMixed,
			...$mixed,
		];
	}

}
