<?php

namespace ResultCachePackageUpdateE2E;

use Test\Logger\Logger;

class Foo
{

	public function __construct(private Logger $logger)
	{
	}

	public function doFoo(): void
	{
		$this->logger->info('hello');
	}

}
