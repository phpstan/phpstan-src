<?php

namespace ResultCachePackageUpdateE2E;

use Psr\Log\LoggerInterface;

class Foo
{

	public function __construct(private LoggerInterface $logger)
	{
	}

	public function doFoo(): void
	{
		$this->logger->info('hello');
	}

}
