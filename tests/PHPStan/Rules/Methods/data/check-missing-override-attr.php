<?php

namespace CheckMissingOverrideAttr;

class Foo
{

	public function doFoo(): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): void
	{

	}

}
