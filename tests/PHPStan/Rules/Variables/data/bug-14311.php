<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14311;

class Station {
	public string $address = '';
}

/** @param array<string, Station> $stations */
function withNullCoalesce(array $stations, string $key): string {
	$bar = $stations[$key] ?? null;
	return $bar?->address ?? 'Unknown';
}

/** @param array<string, Station> $stations */
function withIsset(array $stations, string $key): string {
	$bar = isset($stations[$key]) ? $stations[$key] : null;
	return $bar?->address ?? 'Unknown';
}

/** @param array<string, Station> $stations */
function withArrayKeyExists(array $stations, string $key): string {
	$bar = array_key_exists($key, $stations) ? $stations[$key] : null;
	return $bar?->address ?? 'Unknown';
}
