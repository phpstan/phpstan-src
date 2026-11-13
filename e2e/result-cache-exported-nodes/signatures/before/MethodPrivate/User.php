<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodPrivate;

class User extends Foo
{

	public function user(): int
	{
		return $this->value();
	}

}
