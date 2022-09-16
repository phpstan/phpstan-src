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

	assertType('non-empty-array<int, string>', $mapped);
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

	assertType('list<string>', $mapped);
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

	assertType('non-empty-list<string>', $mapped);
}

/** @param array{foo?: 0, bar?: 1, baz?: 2} $array */
function foo5(array $array): void {
	$mapped = array_map(
		static function(string $string): string {
			return (string) $string;
		},
		$array
	);

	assertType('array{foo?: string, bar?: string, baz?: string}', $mapped);
}
