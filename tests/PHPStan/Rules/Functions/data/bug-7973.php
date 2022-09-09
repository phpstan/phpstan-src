<?php declare(strict_types = 1);

namespace Bug7973;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

function createFromString(string $timespec): DateTime {
	try {
		return new DateTime($timespec, new DateTimeZone('UTC'));
	} catch (Exception) {
		throw new InvalidArgumentException(sprintf('Invalid timespec "%s"', $timespec));
	}
}

function () {
	$rows = [
		['timespec'=>null],
		['timespec'=>'2020-01-01T01:02:03+08:00'],
	];

	$result = [];
	foreach($rows as $row) {
		$result[] = ($row['timespec'] ?? null) !== null ? createFromString($row['timespec']) : null;
	}
};
