<?php

namespace Bug14150Binary;

class Foo
{

	public function doFoo(): void
	{
		1
		+ 2
		+ 'e';
	}

}
