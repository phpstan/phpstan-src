<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug14630;

use function PHPStan\Testing\assertType;

/**
 * @param object[] $a
 * @param object[] $b
 */
function d(array $a, array $b, ?int $i): void
{
	foreach ($a as $afterDynamicPeriodDetail) {
		$beforeDynamicPeriodKey = array_find_key($b, static fn ($beforeDynamicPeriodDetail): bool => $beforeDynamicPeriodDetail->getRange()->equals($afterDynamicPeriodDetail->getRange()));
		if ($beforeDynamicPeriodKey === null) {
			$splitFromPeriodRange = $i;
			if ($splitFromPeriodRange !== null) {
				$beforeDynamicPeriodKey = array_find_key($b, static fn ($beforeDynamicPeriodDetail): bool => $beforeDynamicPeriodDetail->getRange()->equals($splitFromPeriodRange));
			}

			assertType('int|string|null', $beforeDynamicPeriodKey);
			if ($beforeDynamicPeriodKey === null) {
				continue;
			}
		}
	}
}

/**
 * @param object[] $b
 */
function arrayFindKeyNullDoesNotImplyEmptyArray(array $b): void
{
	$key = array_find_key($b, static fn ($v): bool => $v->foo());
	if ($key === null) {
		assertType('array<object>', $b);
		$key2 = array_find_key($b, static fn ($v): bool => $v->bar());
		assertType('int|string|null', $key2);
	}
}

/**
 * @param object[] $b
 */
function arrayFindKeyNotNullImpliesNonEmptyArray(array $b): void
{
	$key = array_find_key($b, static fn ($v): bool => $v->foo());
	if ($key !== null) {
		assertType('non-empty-array<object>', $b);
	}
}
