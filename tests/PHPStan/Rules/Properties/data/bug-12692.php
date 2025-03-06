<?php

declare(strict_types=1);

namespace Bug12692;

class Foo
{

	public static $static;

	public function foo()
	{
		$this->static;
	}

}
