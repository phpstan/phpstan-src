<?php // lint >= 8.3
declare(strict_types = 1);

namespace Bug15224Php83;

/**
 * @phpstan-pure
 */
function test1(): string
{
	return mb_strcut('foo', 1) .
		   mb_strcut('foo', 1, encoding: 'UTF-8') .
		   mb_str_pad('foo', 123, 'x') .
		   mb_str_pad('foo', 123, 'x', encoding: 'UTF-8')
		;
}

/**
 * @phpstan-pure
 */
function test2a(): int|false
{
	return mb_strpos('foo', 'x', 0);
}

/**
 * @phpstan-pure
 */
function test2b(): int|false
{
	return mb_strpos('foo', 'x', 0, encoding: 'UTF-8');
}

/**
 * @phpstan-pure
 * @return array<string>
 */
function test3a(): array
{
	return mb_str_split('foo');
}

/**
 * @phpstan-pure
 * @return array<string>
 */
function test3b(): array
{
	return mb_str_split('foo', encoding: 'UTF-8');
}
