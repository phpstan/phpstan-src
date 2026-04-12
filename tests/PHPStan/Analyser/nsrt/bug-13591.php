<?php

declare(strict_types = 1);

namespace Bug13591;

use function PHPStan\Testing\assertType;

function processHotel(int $hotelId): void {}

/**
 * @param 'get_rooms'|'get_hotels' $action
 */
function test(string $action, ?int $hotelId): void
{
	if ($action === 'get_rooms' && $hotelId === null) {
		throw new \InvalidArgumentException('Hotel ID is required');
	}

	if ($action === 'get_rooms') {
		assertType('int', $hotelId);
		processHotel($hotelId);
	}
}
