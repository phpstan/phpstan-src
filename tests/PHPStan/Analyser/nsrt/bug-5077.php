<?php

namespace Bug5077;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, array<string, int|string>> $array
 */
function test(array &$array): void
{
	$array[] = ['test' => rand(), 'p' => 'test'];
}

function (): void {
	$array = [];
	$array['key'] = [];

	assertType('array{key: array{}}', $array);
	assertType('array{}', $array['key']);

	test($array['key']);
	assertType('array{key: array<int, array<string, int|string>>}', $array);
	assertType('array<int, array<string, int|string>>', $array['key']);

	test($array['key']);
	assertType('array{key: array<int, array<string, int|string>>}', $array);
	assertType('array<int, array<string, int|string>>', $array['key']);

	test($array['key']);
	assertType('array{key: array<int, array<string, int|string>>}', $array);
	assertType('array<int, array<string, int|string>>', $array['key']);
};
