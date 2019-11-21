<?php

namespace TooWideMethodReturnType74;

interface Foo
{

	public function doFoo(): ?string;

}

class Bar implements Foo
{

	public function doFoo(): ?string
	{
		return 'fooo';
	}

}
