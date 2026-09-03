<?php

namespace ResultCacheE2EPathRepository;

use Psr\Log\LoggerInterface;

class UsesPsrLog
{

	public function __construct(private LoggerInterface $logger)
	{
	}

	public function doUsesPsrLog(): void
	{
		$this->logger->info('hello');
	}

}
