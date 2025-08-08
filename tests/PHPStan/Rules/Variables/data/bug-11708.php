<?php declare(strict_types = 1);

namespace Bug11708;

class HelloWorld
{
	public function sayHello(): void
	{
		$xRequestStart = sprintf('t=%s', uniqid('fake_timestamp_'));

		$matches = [];
		if (false === preg_match('/^t=(\d+)$/', (string) $xRequestStart, $matches)) {
			return;
		}

		$requestStart = $matches[1] ?? null;
		$requestStart2 = isset($matches[1]);
	}
}
