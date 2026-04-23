<?php declare(strict_types = 1);

namespace Bug13663;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, array{foo: array<int, mixed>, count: int}> $usageDetailMap
 */
function test(array $usageDetailMap): void {
	assertType('array<int, array{foo: array<int, mixed>, count: int}>', $usageDetailMap);

	foreach ([1,2] as $projectNumberId) {
		assertType('array<int, array{foo: array<int, mixed>, count: int}>', $usageDetailMap);
		if (!array_key_exists($projectNumberId, $usageDetailMap)) {
			$usageDetailMap[$projectNumberId] = [
			  'foo'   => [],
			  'count' => 0,
			];
		}

		$usageDetailMap[$projectNumberId]['count'] = $usageDetailMap[$projectNumberId]['count'] + 1;

		foreach ($usageDetailMap as $existingProjectNumberId => $value) {
			$usageDetailMap[$existingProjectNumberId]['foo'][] = 'foo';
		}

		$usageDetailMap[$projectNumberId]['count'] = $usageDetailMap[$projectNumberId]['count'] + 1;
	}
}
