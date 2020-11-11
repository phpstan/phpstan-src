<?php // lint >= 8.0

namespace NullsafeUnusedPrivateMethod;

class Foo
{

	public function doFoo(?self $self): void
	{
		$self?->doBar();
	}

	private function doBar(): void
	{

	}

}
