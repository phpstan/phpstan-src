<?php

namespace PropertyNativeTypes;

class Foo
{

	private string $stringProp;

	private self $selfProp;

	/** @var int[] */
	private array $integersProp;

	public function doFoo()
	{
		die;
	}

}
