<?php declare(strict_types = 1);

namespace Bug5623;

use DateTimeInterface;

class HelloWorld
{
	public function sayHello(?DateTimeInterface $from = null, ?DateTimeInterface $to = null,): void
	{
		if ($from || $to) {
			$operator = $from ? 'notBefore' : 'notAfter';
			$date = ($from ?? $to)->format(DateTimeInterface::ATOM);
		}
	}
}
