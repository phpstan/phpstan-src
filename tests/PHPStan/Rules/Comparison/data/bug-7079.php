<?php declare(strict_types = 1);

namespace Bug7079;

interface factory {
}

/**
 * @param class-string<factory> $interfaces
 * @param class-string<object> $classes
 */
function foo(string $interfaces, string $classes): bool
{
	return is_subclass_of($interfaces, $classes);
}
