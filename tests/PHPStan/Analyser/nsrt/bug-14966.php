<?php declare(strict_types = 1);

namespace Bug14966;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $haystack
 */
function resolve(?string $needle, array $haystack): string
{
	if ($needle !== null && in_array($needle, $haystack)) {
		return $needle;
	} elseif ($haystack !== []) {
		// The `&&` can also be false when `$needle` is a non-null string that is
		// simply not in `$haystack`, so re-narrowing `$haystack` to non-empty here
		// must not leak `$needle = null` out of the first `if`.
		assertType('string|null', $needle);
		if ($needle !== null) {
			return 'other:' . $needle;
		}
		return 'null-branch';
	}

	return 'empty';
}

/**
 * The narrowing must still fire when the guard genuinely selects the branch:
 * re-asserting the same `$needle !== null` really does imply `in_array()` decided
 * the first `if`, so the sound projection is preserved.
 *
 * @param list<string> $haystack
 */
function soundNarrowing(?string $needle, array $haystack): void
{
	if ($needle !== null && in_array($needle, $haystack, true)) {
		assertType('string', $needle);
	}
}
