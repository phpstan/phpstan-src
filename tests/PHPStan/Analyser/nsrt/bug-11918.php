<?php declare(strict_types = 1);

namespace Bug11918;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, list<mixed>|string|false> $options
 */
function testArrayKeyExistsCoalesce(array $options): void
{
	if (array_key_exists('a', $options) && !is_string($options['a'])) {
		exit(1);
	}

	$a = $options['a'] ?? 'fallback';
	assertType('string', $a);
}
