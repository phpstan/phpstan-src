<?php

namespace AllowedSubtypesDateTime;

use DateTime;
use DateTimeInterface;
use function PHPStan\Testing\assertType;

function foo(DateTimeInterface $dateTime): void {
	assertType('DateTimeInterface', $dateTime);

	if ($dateTime instanceof DateTime) {
		return;
	}

	assertType('DateTimeImmutable', $dateTime);
}
