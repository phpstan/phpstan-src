<?php

namespace FixSameFileTrait;

trait SameFileTrait
{

	public function doFoo(): void
	{
		echo $b;
	}

}

class Foo
{

	use SameFileTrait;

}
