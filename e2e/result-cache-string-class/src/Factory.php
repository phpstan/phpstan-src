<?php declare(strict_types = 1);

namespace ResultCacheE2EStringClass;

class Factory
{

	public function create(): object
	{
		$class = 'ResultCacheE2EStringClass\Thing';

		return new $class();
	}

}
