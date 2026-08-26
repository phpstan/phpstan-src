<?php

namespace TraitAliases;

trait BazTrait
{

	public function fooMethod(): void
	{

	}

}

trait BarTrait
{

	use BazTrait {
		fooMethod as parentFooMethod;
	}

	public function fooMethod(): void
	{
		// some code ...
		if (rand(0, 1) === 1) {
			$this->fooMethod();
		}
		$this->parentFooMethod();
	}

}

class Foo
{

	use BarTrait;

	public function doFoo(): void
	{
		$this->fooMethod();
		$this->parentFooMethod();
	}

}
