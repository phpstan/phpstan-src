<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterPhpDocAdded;

class Foo
{

	public function __construct(
		/** @var list<int> */
		public array $values,
	)
	{
	}

}
