<?php declare(strict_types = 1);

namespace ExtDsVersionE2E;

class Foo
{

	public function doFoo(): void
	{
		// Ds\Vector exists only in ext-ds 1, Ds\Seq only in ext-ds 2.
		new \Ds\Vector();
		new \Ds\Seq();
	}

}
