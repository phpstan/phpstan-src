<?php declare(strict_types = 1);

namespace Bug9401;

/**
 * @param array<mixed> $foos
 * @return list<positive-int>
 */
function foo(array $foos): array
{
	$list = [];
	foreach ($foos as $foo) {
		if (is_int($foo) && $foo >= 0) {
			$list[] = $foo;
		}
	}
	return $list;
}
