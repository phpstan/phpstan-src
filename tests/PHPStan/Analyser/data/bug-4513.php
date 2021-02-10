<?php

namespace Bug4513;

class Foo
{

	use BarTrait;

}

trait BarTrait
{

	public function doFoo(): void
	{
		// @phpstan-ignore-next-line
		echo 'foo';
	}

	public function doBar(): void
	{
		// @phpstan-ignore-next-line
		echo [];
	}

}
