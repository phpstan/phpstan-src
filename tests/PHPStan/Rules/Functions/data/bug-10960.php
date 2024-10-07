<?php declare(strict_types = 1);

namespace Bug10960;

/**
 * @param array{foo: string} $foo
 *
 * @return array{FOO: string}
 */
function upperCaseKey(array $foo): array
{
	return array_change_key_case($foo, CASE_UPPER);
}

/**
 * @param array{FOO: string} $foo
 *
 * @return array{foo: string}
 */
function lowerCaseKey(array $foo): array
{
	return array_change_key_case($foo, CASE_LOWER);
}

upperCaseKey(['foo' => 'bar']);
lowerCaseKey(['FOO' => 'bar']);
