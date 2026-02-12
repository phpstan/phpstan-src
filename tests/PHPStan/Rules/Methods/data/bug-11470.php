<?php declare(strict_types = 1);

namespace Bug11470;

use DateTimeImmutable;

interface HelloWorld
{
	public function sayHello(): dateTimeImmutable;
}

interface HelloWorld2
{
	public function sayHello(dateTimeImmutable $a): void;
}

interface HelloWorld3
{
	public function sayHello(): ?dateTimeImmutable;
}
