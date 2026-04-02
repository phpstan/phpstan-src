<?php

namespace Bug13591;

use function PHPStan\Testing\assertType;

function processAction(string $action, ?int $hotelId): void
{
	if ($hotelId === null && ($action === 'get_rooms' || $action === 'update')) {
		throw new \InvalidArgumentException('Hotel ID is required');
	}

	if ($action === 'get_rooms') {
		assertType('int', $hotelId);
	}
}
