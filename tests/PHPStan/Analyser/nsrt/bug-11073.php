<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11073Nsrt;

use DateTimeImmutable;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(?DateTimeImmutable $date): void
	{
		assertType('DateTimeImmutable|null', $date?->modify('+1 year')->setTime(23, 59, 59));
	}
}

class Foo
{
	public function getCode(): bool { return false; }
}

class HelloWorld2
{
	public function sayHello(\Throwable|Foo $foo): void
	{
		assertType('bool|int|string', $foo->getCode());
	}

	public function sayHello2(\LogicException|Foo $foo): void
	{
		assertType('bool|int', $foo->getCode());
	}
}
