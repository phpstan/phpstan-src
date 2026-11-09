<?php // lint >= 8.4

namespace Bug15195;

/**
 * @param list<string> $arr
 */
function foo(
	array $arr,
): void {
	$arr1 = array_filter(
		array: $arr,
		callback: static fn(string $s): bool => $s !== '',
	);

	$arr2 = array_filter(
		callback: static fn(string $s): bool => $s !== '',
		array: $arr,
	);
}

/**
 * @param array<string, int> $map
 */
function arrayFilterMode(array $map): void
{
	array_filter(mode: ARRAY_FILTER_USE_KEY, callback: static fn (string $k): bool => $k !== '', array: $map);
	array_filter($map, mode: ARRAY_FILTER_USE_BOTH, callback: static fn (int $v, string $k): bool => $k !== '');
	array_filter($map, mode: ARRAY_FILTER_USE_BOTH, callback: static fn (string $v, int $k): bool => $k !== 0);
}

/**
 * @param array<string, int> $map
 */
function arrayWalk(array $map): void
{
	array_walk(callback: static function (int $v, string $k): void {}, array: $map);
	array_walk(callback: static function (string $v, int $k): void {}, array: $map);
	array_walk(arg: 1, callback: static function (int $v, string $k, int $arg): void {}, array: $map);
	array_walk(arg: 1, callback: static function (int $v, string $k, string $arg): void {}, array: $map);
}

/**
 * @param array<string, int> $map
 */
function arrayFind(array $map): void
{
	array_find(callback: static fn (int $v, string $k): bool => $v > 0, array: $map);
	array_find(callback: static fn (string $v, int $k): bool => $k > 0, array: $map);
	array_find_key(callback: static fn (int $v, string $k): bool => $v > 0, array: $map);
	array_any(callback: static fn (int $v, string $k): bool => $v > 0, array: $map);
	array_all(callback: static fn (int $v, string $k): bool => $v > 0, array: $map);
}

/**
 * @param list<string> $arr
 */
function arrayMap(array $arr): void
{
	array_map(array: $arr, callback: static fn (string $s): string => $s);
	array_map(array: $arr, callback: static fn (int $s): int => $s);
}

function curlSetOpt(\CurlHandle $h): void
{
	curl_setopt(value: 'https://example.com', option: CURLOPT_URL, handle: $h);
	curl_setopt(value: 1, option: CURLOPT_URL, handle: $h);
	curl_setopt($h, value: 1, option: CURLOPT_URL);
	curl_setopt_array(options: [CURLOPT_URL => 'https://example.com'], handle: $h);
	curl_setopt_array(options: [CURLOPT_URL => 1], handle: $h);
}
