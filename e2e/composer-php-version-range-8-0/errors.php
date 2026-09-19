<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange80Errors;

function acceptsThreeInts(int $i, int $j, int $k): void
{
	echo $i + $j + $k;
}

/**
 * @param list<int> $args
 */
function namedArgumentAfterUnpackedArgument(array $args): void
{
	// a named argument after an unpacked one is only allowed since PHP 8.1
	acceptsThreeInts(...$args, k: 3);
}
