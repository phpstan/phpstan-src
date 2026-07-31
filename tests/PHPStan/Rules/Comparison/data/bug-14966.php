<?php declare(strict_types=1);

namespace Bug14966Rule;

use function in_array;

/**
 * @param list<string> $haystack
 */
function resolve(?string $needle, array $haystack): string
{
    if ($needle !== null && in_array($needle, $haystack)) {
        return $needle;
    } elseif ($haystack !== []) {
        if ($needle !== null) {
            return 'other:' . $needle;
        }
        return 'null-branch';
    }

    return 'empty';
}
