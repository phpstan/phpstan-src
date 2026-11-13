<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterPhpDocRemoved;

class Foo
{

	public function __construct(
		/** @var list<int> */
		public array $values,
	)
	{
	}

}
