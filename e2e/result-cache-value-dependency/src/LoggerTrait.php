<?php

namespace ResultCacheE2EValueDependency;

trait LoggerTrait
{

	public function logSomething(): void
	{
		service('logger')->log();
	}

}
