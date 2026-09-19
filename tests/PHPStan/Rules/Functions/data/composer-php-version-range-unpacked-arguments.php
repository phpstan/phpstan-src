<?php // lint >= 8.1

namespace ComposerPhpVersionRangeUnpackedArguments;

function doFoo(int $i, int $j, int $k): void
{

}

/**
 * @param list<int> $args
 */
function doBar(array $args): void
{
	doFoo(...$args, k: 3);
}
