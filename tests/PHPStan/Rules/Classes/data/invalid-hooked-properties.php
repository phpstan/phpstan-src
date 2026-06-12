<?php // lint >= 8.4

namespace InvalidHookedProperties;

use DateTimeImmutable;

class HelloWorld
{
	public function sayHello(DateTimeImmutable $date { get; }): void
	{

	}
}

class ValidPromotedProperty
{
	public function __construct(DateTimeImmutable $date { get {} })
	{

	}
}
