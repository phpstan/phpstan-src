<?php

namespace TestResultCache8;

/**
 * @phpstan-require-implements Baz
 */
trait Bar
{
	public function doFoo(): void
	{
		echo $this->doFoo();
	}
}
