<?php declare(strict_types = 1);

// The mockery/mockery shape of a polyfill: declared behind a function_exists()
// guard, so it never runs on a PHP version that has the function natively -
// and shipping a PHPDoc that contradicts the native signature.

if (!function_exists('str_contains')) {
	/**
	 * @param non-empty-string $haystack
	 * @param non-empty-string $needle
	 */
	function str_contains(string $haystack, string $needle): bool
	{
		return $needle === '' || strpos($haystack, $needle) !== false;
	}
}

if (!function_exists('str_starts_with')) {
	/**
	 * @param non-empty-string $haystack
	 * @param non-empty-string $needle
	 */
	function str_starts_with(string $haystack, string $needle): bool
	{
		return strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}

if (!function_exists('str_ends_with')) {
	/**
	 * @param non-empty-string $haystack
	 * @param non-empty-string $needle
	 */
	function str_ends_with(string $haystack, string $needle): bool
	{
		return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
	}
}
