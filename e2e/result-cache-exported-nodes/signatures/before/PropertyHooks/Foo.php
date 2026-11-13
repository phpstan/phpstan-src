<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyHooks;

class Foo
{

	public int $value = 1 {
		set(int|string $value) {
			$this->value = (int) $value;
		}
	}

}
