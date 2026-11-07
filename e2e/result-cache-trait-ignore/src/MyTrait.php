<?php

namespace Repro;

trait MyTrait
{
	public function doFoo(): void
	{
		// @phpstan-ignore method.notFound
		$this->undefinedMethod();
		// @phpstan-ignore-next-line
		$this->anotherUndefinedMethod();
	}
}
