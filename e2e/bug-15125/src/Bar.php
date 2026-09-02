<?php

namespace Bug15125;

use Test\Logger\Logger;

final class Bar
{

	public function __construct(private Logger $logger)
	{
	}

	public function doBar(): void
	{
		$this->logger->info('hello');
	}

}
