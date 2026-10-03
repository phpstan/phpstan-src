<?php declare(strict_types = 1); // lint >= 8.0

namespace NamedArgumentsOrderIntrinsic;

use CurlHandle;

/**
 * @param list<string> $arr
 * @param array<string, int> $map
 */
function arrayFilter(array $arr, array $map): void
{
	array_filter(callback: static fn (string $s): bool => $s !== '', array: $arr);
	array_filter(callback: static fn (int $i): bool => $i !== 0, array: $arr);
	array_filter(mode: ARRAY_FILTER_USE_KEY, callback: static fn (string $k): bool => $k !== '', array: $map);
	array_filter(mode: ARRAY_FILTER_USE_KEY, callback: static fn (int $k): bool => $k !== 0, array: $map);
	array_filter(mode: ARRAY_FILTER_USE_BOTH, callback: static fn (int $v, string $k): bool => true, array: $map);
	array_filter(mode: ARRAY_FILTER_USE_BOTH, callback: static fn (string $v, int $k): bool => true, array: $map);
}

/**
 * @param list<string> $arr
 */
function arrayMap(array $arr): void
{
	array_map(callback: static fn (string $s): string => $s, array: $arr);
	array_map(array: $arr, callback: static fn (string $s): string => $s);
	array_map(array: $arr, callback: static fn (int $i): int => $i);
}

/**
 * @param list<string> $arr
 */
function arrayWalk(array $arr): void
{
	array_walk(callback: static function (string $v, int $k): void {}, array: $arr);
	array_walk(callback: static function (int $v, int $k): void {}, array: $arr);
	array_walk(arg: 1.0, callback: static function (string $v, int $k, float $a): void {}, array: $arr);
	array_walk(arg: 1.0, callback: static function (string $v, int $k, string $a): void {}, array: $arr);
}

function curl(CurlHandle $ch): void
{
	curl_setopt(value: 2, option: CURLOPT_SSL_VERIFYHOST, handle: $ch);
	curl_setopt(value: 'foo', option: CURLOPT_SSL_VERIFYHOST, handle: $ch);
	curl_setopt_array(options: [CURLOPT_SSL_VERIFYHOST => 2], handle: $ch);
	curl_setopt_array(options: [CURLOPT_SSL_VERIFYHOST => 'foo'], handle: $ch);
}

/**
 * @param list<string> $arr
 */
function implodes(array $arr): void
{
	implode(array: $arr, separator: ',');
	implode(array: 'foo', separator: ',');
}
