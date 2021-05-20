<?php

namespace PHPStan\Foo;

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
		new FileTypeMapper();
		new NeonAdapter();
	}

}

class Bar
{

}
