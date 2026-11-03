<?php // lint >= 8.3

namespace FixOverrideAttributeImported;

use Override;

class Foo
{

	public function doFoo(): void
	{

	}

	public function doBar(): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): void
	{

	}

	public function doBar(): void
	{

	}

}
