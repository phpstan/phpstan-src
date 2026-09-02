<?php declare(strict_types = 1);

namespace TestResultCacheTruncated;

class Foo
{

	public function doFoo(Bar $bar): string
	{
		return $bar->doBar();
	}

}
