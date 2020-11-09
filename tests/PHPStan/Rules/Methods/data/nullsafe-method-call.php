<?php // lint >= 8.0

namespace NullsafeMethodCall;

class Foo
{

	public function doFoo(?self $selfOrNull): void
	{
		$selfOrNull?->doBar();
		$selfOrNull?->doBar(1);
	}

	public function doBar(): void
	{

	}

}
