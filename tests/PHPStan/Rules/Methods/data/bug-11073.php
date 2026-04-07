<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11073;

use DateTimeImmutable;

class HelloWorld
{
	public function sayHello(?DateTimeImmutable $date): ?DateTimeImmutable
	{
		return $date?->modify('+1 year')->setTime(23, 59, 59);
	}
}
