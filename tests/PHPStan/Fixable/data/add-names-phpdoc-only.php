<?php

namespace AddNamesPhpDocOnly;

use AddNamesImported\Foo;

class Bar
{

	/**
	 * @add-doc-name \AddNamesImported\Foo
	 * @add-doc-name \AddNamesOther\AddNamesUnique
	 */
	public function doFoo($p1, $p2): void
	{
	}

}
