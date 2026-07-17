<?php declare(strict_types=1);

namespace BugRule14966;

/**
 * @param list<string> $haystack
 */
function resolve(?string $needle, array $haystack): string
{
	if ($needle !== null && in_array($needle, $haystack)) {
		return $needle;
	} elseif ($haystack !== []) {
		// $needle is still `string|null` here, but PHPStan narrowed it to `null`.
		if ($needle !== null) {                 // <-- reported "always false"
			return 'other:' . $needle;
		}
		return 'null-branch';
	}

	return 'empty';
}
