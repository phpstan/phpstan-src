<?php declare(strict_types = 1);

namespace ResultCacheE2EVendorClassAppears;

use Psr\Log\PluginProvidedLogger;

class Foo
{

	public function doFoo(PluginProvidedLogger $logger): void
	{
		$logger->log('hello');
	}

}
