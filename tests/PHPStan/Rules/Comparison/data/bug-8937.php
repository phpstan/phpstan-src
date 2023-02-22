<?php // lint >= 8.0

namespace Bug8937;

/**
 * @param 'A'|'B' $string
 */
function sayHello(string $string): int
{
	return match ($string) {
		'A' => 1,
		'B' => 2,
	};
}

/**
 * @param array<array-key,string>|string $v
 */
function foo(array|string $v): string
{
	return match(true) {
		is_string($v) => 'string',
		is_array($v) && \array_is_list($v) => 'list',
		is_array($v) => 'array',
	};
}
