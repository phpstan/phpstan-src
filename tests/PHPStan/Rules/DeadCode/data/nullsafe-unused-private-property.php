<?php // lint >= 8.0

namespace NullsafeUnusedPrivateProperty;

class Foo
{

	private string $bar = 'foo';

	public function doFoo(?self $self): void
	{
		echo $self?->bar;
	}

}
