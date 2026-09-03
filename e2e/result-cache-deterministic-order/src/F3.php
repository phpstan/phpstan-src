<?php

namespace ResultCacheDeterministicOrderE2E;

use Test\Logger\Logger;
use Test\Mailer\Mailer;

// Mailer is declared before Logger on purpose: the packages of one file are collected in the
// order its dependencies are reflected, so without a sort this file records test/mailer first.
class F3
{

	public function __construct(private Mailer $mailer, private Logger $logger)
	{
	}

	public function doFoo(): void
	{
		$this->mailer->send('hello');
		$this->logger->info('hello');
	}

}
