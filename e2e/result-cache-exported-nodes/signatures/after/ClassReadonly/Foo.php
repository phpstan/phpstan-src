<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassReadonly;

readonly class Foo
{

	public int $x;

	public function __construct()
	{
		$this->x = 1;
	}

}
