<?php

namespace PureUnlessCallableIsImpure;

/**
 * @phpstan-pure-unless-callable-is-impure $callback
 * @param array<int> $array
 * @param callable(int): bool $callback
 * @return array<int>
 */
function myFilter(array $array, callable $callback): array
{
	return array_filter($array, $callback);
}
