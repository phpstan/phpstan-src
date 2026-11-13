<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodByRef;

class Foo
{

	/** @var list<int> */
	private array $values = [];

	/** @return list<int> */
	public function values(): array
	{
		return $this->values;
	}

}
