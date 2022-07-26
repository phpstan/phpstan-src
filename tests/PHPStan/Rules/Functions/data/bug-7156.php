<?php

namespace Bug7156;


use function PHPStan\Testing\assertType;

/**
 * @param array{value: string} $foo
 */
function foo($foo): void {
	print_r($foo);
}

/**
 * @param array<mixed> $data
 */
function foobar(array $data): void
{
	if (!array_key_exists('value', $data) || !is_string($data['value'])) {
		throw new \RuntimeException();
	}

	assertType("array&hasOffsetValue('value', string)", $data);

	foo($data);
}

function foobar2(mixed $data): void
{
	if (!is_array($data) || !array_key_exists('value', $data) || !is_string($data['value'])) {
		throw new \RuntimeException();
	}

	assertType("array&hasOffsetValue('value', string)", $data);

	foo($data);
}
