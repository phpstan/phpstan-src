<?php declare(strict_types = 1);

namespace Bug15461Rule;

/**
 * @param mixed[] $export
 * @param string $key
 * 
 * @return mixed[]
 */
function f(array $export, string $key): array {
	assert(isset($export[$key]));
	assert(is_array($export[$key]));
	unset($export[$key]['x']);
	return $export[$key];
}

/**
 * @param mixed[] $export
 * @param string $key
 * 
 * @return mixed[]
 */
function g(array $export, string $key): array {
	assert(isset($export[$key]));
	assert(is_array($export[$key]));
	unset($export[$key]['x']);
	unset($export[$key]['y']);
	return $export;
}