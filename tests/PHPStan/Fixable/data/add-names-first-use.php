<?php declare(strict_types = 1);

namespace AddNamesFirstUse;

class Foo
{

	/**
	 * @add-name \AddNamesOther\Lorem
	 * @add-name \AddNamesOther\Ipsum
	 * @add-name \AddNamesOther\Lorem
	 */
	public function doFoo(): void
	{
	}

}
