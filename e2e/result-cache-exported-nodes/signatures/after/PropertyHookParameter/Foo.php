<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyHookParameter;

class Foo
{

	public int $value = 1 {
		set(int $value) {
			$this->value = (int) $value;
		}
	}

}
