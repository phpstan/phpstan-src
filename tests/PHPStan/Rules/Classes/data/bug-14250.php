<?php declare(strict_types = 1);

namespace Bug14250;

trait MyTrait
{
	public function doSomething(): void
	{
	}

	public function doSomething(): void
	{
	}
}

class Foo
{
	use MyTrait;
}
