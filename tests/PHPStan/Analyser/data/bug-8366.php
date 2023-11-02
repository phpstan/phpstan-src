<?php

namespace Bug8366;

use function PHPStan\Testing\assertType;

function validateParams(
	?\DateTimeImmutable $datePeriod,
	?\DateTimeImmutable $untilDate,
	?int $count
): void {

	if (isset($untilDate, $count)) {
		assertType('DateTimeImmutable', $untilDate);
		assertType('int', $count);

		throw new \InvalidArgumentException('Too much params, choose between until and count.');
	}
	assertType('DateTimeImmutable|null', $untilDate);
	assertType('int|null', $count);

	if ($untilDate !== null && $datePeriod > $untilDate) {
		assertType('DateTimeImmutable', $untilDate);
		assertType('int|null', $count);
		throw new \InvalidArgumentException('End date must not be greater than until date.');
	}

	if ($count !== null && $count < 1) {
		assertType('DateTimeImmutable|null', $untilDate);
		assertType('int<min, 0>', $count);
		throw new \InvalidArgumentException('Count must be positive.');
	}

	assertType('DateTimeImmutable|null', $untilDate);
	assertType('int<1, max>|null', $count);
}
