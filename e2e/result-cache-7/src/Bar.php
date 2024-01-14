<?php

namespace TestResultCache7;

/**
 * @phpstan-require-extends Baz
 */
interface Bar
{
	public function doFoo():string {
		return 'foo';
	}
}
