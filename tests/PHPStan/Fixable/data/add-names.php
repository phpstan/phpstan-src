<?php

namespace AddNames;

use AddNamesImported\Foo;
use AddNamesImported\Sub;
use AddNamesAliased\Baz as AliasedBaz;

class SameNamespace
{

}

class Bar
{

	/**
	 * @add-name \AddNames\Bar
	 * @add-name \AddNames\SameNamespace
	 * @add-name \AddNamesImported\Foo
	 * @add-name \AddNamesImported\Sub\Lorem
	 * @add-name \AddNamesAliased\Baz
	 * @add-name \AddNamesOther\AddNamesUnique
	 * @add-name \Override
	 * @add-name \AddNamesOther\Foo
	 * @add-name \AddNamesOther\SameNamespace
	 */
	public function doFoo(): void
	{
	}

}
