<?php

namespace Bug11709;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-template T
 * @param ($value is array ? array<T> : mixed) $value
 * @phpstan-assert array<string, T> $value
 */
function isArrayWithStringKeys(mixed $value): void
{
	if (!is_array($value)) {
		throw new \Exception('Not an array');
	}

	foreach (array_keys($value) as $key) {
		if (!is_string($key)) {
			throw new \Exception('Non-string key');
		}
	}
}

function ($m): void {
	isArrayWithStringKeys($m);
	assertType('array<string, mixed>', $m);
};
