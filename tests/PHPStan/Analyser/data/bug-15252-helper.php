<?php declare(strict_types = 1);

namespace Bug15252;

use Ns\Foo;
use Ns\Foo2 as Foo;

class Helper
{

	public function name(): string
	{
		return 'helper';
	}

}
