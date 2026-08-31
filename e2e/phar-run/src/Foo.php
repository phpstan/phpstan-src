<?php declare(strict_types = 1);

namespace PharRun;

final class Foo
{

	public function doFoo(string $s): int
	{
		return strlen($s);
	}

}
