<?php declare(strict_types = 1);

namespace Bug15875;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @param list<object>|null $attributes */
function setController(?array $attributes = null): void
{
	if (null !== $attributes && !array_is_list($attributes)) {
		assertType('*NEVER*', $attributes);
		assertNativeType('non-empty-array', $attributes);
		foreach ($attributes as $attribute) {
			assertNativeType('mixed', $attribute);
		}
	}
}

/** @param array<string, int> $a */
function phpDocOnly($a): void
{
	if (!array_is_list($a)) {
		assertType('non-empty-array<string, int>', $a);
		assertNativeType('non-empty-array', $a);
	}
}
