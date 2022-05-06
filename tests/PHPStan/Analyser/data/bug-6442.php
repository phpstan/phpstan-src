<?php

namespace Bug6442;

trait T
{
	public function x(): void
	{
		\PHPStan\dumpType(parent::class);
	}
}

class A {}

class B extends A
{
	use T;
}

new class() extends B
{
	use T;
};

