<?php // lint >= 8.0

namespace NullsafePropertyFetch;

class Foo
{

	private $bar;

	public function doFoo(?self $selfOrNull): void
	{
		$selfOrNull?->bar;
		$selfOrNull?->baz;
	}

}
