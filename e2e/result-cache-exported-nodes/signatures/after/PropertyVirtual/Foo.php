<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyVirtual;

class Foo
{

	public int $value {
		get => $this->value;
	}

}
