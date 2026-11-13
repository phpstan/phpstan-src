<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyReadonly;

class Foo
{

	public readonly int $value;

	public function __construct()
	{
		$this->value = 1;
	}

}
