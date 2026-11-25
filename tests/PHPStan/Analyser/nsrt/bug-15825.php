<?php declare(strict_types = 1);

namespace Bug15825;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, array{properties: array{id: int, date_creation?: string}}> $schemas
 * @return array<string, array{properties: array{id: int, date_creation?: string}}>
 */
function removeDates(array $schemas): array
{
	unset($schemas['GenericDeviceType']['properties']['date_creation']);
	assertType('array<string, array{properties: array{id: int, date_creation?: string}}>', $schemas);
	unset($schemas['SensorType']['properties']['date_creation']);

	return $schemas;
}
