<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15258;

/**
 * Given a string of the shape 'foo-bar', extract the prefix 'foo-'
 * @phpstan-pure
 * @param non-empty-string $x
 * @return non-empty-string
 */
function extract_prefix(string $x): string {
	$pos = strpos($x, '-');
	if ($pos === false)
        throw new \ValueError('cannot extract a prefix from a string that has no prefix');
    return substr($x, 0, $pos + 1);
}
