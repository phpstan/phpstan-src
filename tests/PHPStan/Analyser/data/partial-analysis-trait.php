<?php

namespace PartialAnalysisTrait;

trait FooTrait
{

	public function doFoo(): void
	{
		fail();
	}

}

// another class that uses the trait is outside the analysed paths
class Foo
{

	use FooTrait;

}
