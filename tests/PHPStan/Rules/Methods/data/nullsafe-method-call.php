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

	public function doBaz(&$passedByRef): void
	{

	}

	public function doLorem(?self $selfOrNull): void
	{
		$this->doBaz($selfOrNull?->test);
		$this->doBaz($selfOrNull?->test->test);
	}

	public function doNull(): void
	{
		$null = null;
		$null->foo();
		$null?->foo();
	}

}
