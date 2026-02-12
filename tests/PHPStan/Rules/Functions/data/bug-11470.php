<?php declare(strict_types = 1);

namespace Bug11470Functions;

use DateTimeImmutable;

function sayHello(): dateTimeImmutable
{
	return new DateTimeImmutable();
}

function sayHello2(dateTimeImmutable $a): void
{
}

function sayHello3(): ?dateTimeImmutable
{
	return null;
}
