<?php

namespace ArrayMap;

use function array_map;
use function PHPStan\Testing\assertType;

/**
 * @param array<int, string> $array
 */
function foo(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array<int, string>', $mapped);
}

/**
 * @param non-empty-array<int, string> $array
 */
function foo2(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array<int, string>&nonEmpty', $mapped);
}

/**
 * @param list<string> $array
 */
function foo3(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array<int<0, max>, string>', $mapped);
}

/**
 * @param non-empty-list<string> $array
 */
function foo4(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array<int<0, max>, string>&nonEmpty', $mapped);
}
