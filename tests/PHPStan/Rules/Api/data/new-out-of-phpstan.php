<?php

namespace AppNew;

use PHPStan\DependencyInjection\NeonAdapter;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\IntegerType;

class Foo
{

	public function doFoo(): void
	{
		new Nonexistent();
		new Bar();
		new IntegerType();
		new FileTypeMapper(); // error - has constructor
		new NeonAdapter(); // error - does not have a constructor
	}

}

class Bar
{

}
