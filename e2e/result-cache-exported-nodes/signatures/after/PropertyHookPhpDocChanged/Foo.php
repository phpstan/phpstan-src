<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PropertyHookPhpDocChanged;

class Foo
{

	public int $value = 1 {
		/** @param negative-int $value */
		set(int $value) {
			$this->value = $value;
		}
	}

}
