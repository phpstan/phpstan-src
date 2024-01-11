<?php

namespace TestResultCache8;

/**
 * @phpstan-require-implements Baz
 */
trait Bar
{
	public function doBar(): void
	{
		echo $this->doFoo();
	}
}
