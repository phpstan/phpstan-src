<?php

namespace Bug8366;

use function PHPStan\Testing\assertType;

function validateParams(
	?\DateTimeImmutable $datePeriod,
	?\DateTimeImmutable $untilDate,
	?int $count
): void {

	if (isset($untilDate, $count)) {
		throw new \InvalidArgumentException('Too much params, choose between until and count.');
	}
	assertType('DateTimeImmutable|null', $untilDate);
	assertType('int|null', $count);

	if ($untilDate !== null && $datePeriod > $untilDate) {
		throw new \InvalidArgumentException('End date must not be greater than until date.');
	}

	if ($count !== null && $count < 1) {
		throw new \InvalidArgumentException('Count must be positive.');
	}
}
