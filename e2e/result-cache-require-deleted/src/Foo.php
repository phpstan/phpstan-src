<?php declare(strict_types = 1);

namespace ResultCacheE2ERequireDeleted;

class Foo
{

	public function load(): mixed
	{
		return require 'config/app.php';
	}

}
