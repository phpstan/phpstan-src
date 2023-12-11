<?php

namespace Bug8166;

/**
 * @template T of int|string
 *
 * @param array<T,mixed> $arr
 *
 * @return array<T,string>
 */
function strings(array $arr): array
{
	return $arr;
}

function (): void {
	$x = ['a' => 1];

	$y = strings($x);

	var_dump($x['b']);
	var_dump($y['b']);
};
