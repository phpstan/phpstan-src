<?php

namespace ResultCacheDeterministicOrderE2E;

use Test\Logger\Logger;
use Test\Mailer\Mailer;

// without the sort, this file records test/mailer before test/logger
class F1
{

	public function __construct(private Logger $logger, private Mailer $mailer)
	{
	}

	public function doFoo(): void
	{
		$this->logger->info('hello');
		$this->mailer->send('hello');
	}

}
