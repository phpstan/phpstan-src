<?php declare(strict_types=1);

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
        // reaching this branch does not imply $needle is null - the && is
        // also false for a non-null $needle that is not in the haystack
        assertType('string|null', $needle);
        if ($needle !== null) {
            return 'other:' . $needle;
        }
        return 'null-branch';
    }

    return 'empty';
}
