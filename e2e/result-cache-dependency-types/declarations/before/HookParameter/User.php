<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\HookParameter;

class User
{

	public int $value = 1 {
		set(int|Foo $value) {
			$this->value = 1;
		}
	}

}
