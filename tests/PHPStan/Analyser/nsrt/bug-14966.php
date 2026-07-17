<?php declare(strict_types = 1);

namespace Bug14966;

use function PHPStan\Testing\assertType;
use function in_array;

/**
 * @param list<string> $haystack
 */
function resolve(?string $needle, array $haystack): string
{
	if ($needle !== null && in_array($needle, $haystack)) {
		return $needle;
	} elseif ($haystack !== []) {
		// Reaching here does not imply $needle is null: the `&&` is also false
		// when $needle is a non-null string that simply is not in $haystack.
		assertType('string|null', $needle);
		if ($needle !== null) {
			return 'other:' . $needle;
		}
		return 'null-branch';
	}

	return 'empty';
}

function plainArray(?string $needle, array $haystack): void
{
	if ($needle !== null && in_array($needle, $haystack)) {
		return;
	}

	if ($haystack !== []) {
		assertType('string|null', $needle);
	}
}

/**
 * @param list<int> $haystack
 */
function intNeedle(?int $needle, array $haystack): void
{
	if ($needle !== null && in_array($needle, $haystack)) {
		return;
	}

	if ($haystack !== []) {
		assertType('int|null', $needle);
	}
}

/**
 * @param list<string> $haystack
 */
function strictStaysNarrowed(?string $needle, array $haystack): void
{
	if ($needle !== null && in_array($needle, $haystack, true)) {
		return;
	}

	if ($haystack !== []) {
		assertType('string|null', $needle);
	}
}
