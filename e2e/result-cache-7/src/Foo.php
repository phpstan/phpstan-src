<?php

namespace TestResultCache7;

/** @phpstan-require-implements Bar */
trait Foo
{
	public function doBar() {
		echo $this->doFoo();
	}
}
