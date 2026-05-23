<?php declare(strict_types = 1);

namespace Bug8980;

use function class_exists;
use function enum_exists;
use function function_exists;
use function interface_exists;
use function trait_exists;

// function_exists: guard with early return, then redundant check
function testFunctionExistsGuardReturn(): void {
	if (!function_exists('curl_init')) {
		return;
	}
	if (function_exists('curl_init')) {
		echo 'exists';
	}
}

// function_exists: guard with throw, then redundant check
function testFunctionExistsGuardThrow(): void {
	if (!function_exists('curl_init')) {
		throw new \RuntimeException();
	}
	if (function_exists('curl_init')) {
		echo 'exists';
	}
}

// class_exists: guard with early return, then redundant check
function testClassExistsGuardReturn(): void {
	if (!class_exists('SomeClass8980')) {
		return;
	}
	if (class_exists('SomeClass8980')) {
		echo 'exists';
	}
}

// interface_exists: guard with early return, then redundant check
function testInterfaceExistsGuardReturn(): void {
	if (!interface_exists('SomeInterface8980')) {
		return;
	}
	if (interface_exists('SomeInterface8980')) {
		echo 'exists';
	}
}

// trait_exists: guard with early return, then redundant check
function testTraitExistsGuardReturn(): void {
	if (!trait_exists('SomeTrait8980')) {
		return;
	}
	if (trait_exists('SomeTrait8980')) {
		echo 'exists';
	}
}

// enum_exists: guard with early return, then redundant check
function testEnumExistsGuardReturn(): void {
	if (!enum_exists('SomeEnum8980')) {
		return;
	}
	if (enum_exists('SomeEnum8980')) {
		echo 'exists';
	}
}

// function_exists: constructor guard, then check in method
final class ConstructorGuard
{
	public function __construct()
	{
		if (!function_exists('curl_init')) {
			throw new \RuntimeException();
		}
	}

	public function doFoo(): void
	{
		if (function_exists('curl_init')) {
			echo 'exists';
		}
	}
}

// Multiple different function_exists checks without guards (should not trigger)
function testMultipleFunctionExists(): void {
	if (function_exists('curl_init')) {
		echo 'curl_init exists';
	}
	if (function_exists('curl_multi_init')) {
		echo 'curl_multi_init exists';
	}
}

// function_exists in && (should not trigger)
function testFunctionExistsAnd(): void {
	if (function_exists('curl_init') && function_exists('curl_multi_init')) {
		echo 'both exist';
	}
}

// actual bug report snippet: function_exists inside array_filter callback
$undefined_curl_functions = array_filter(
	[
		'curl_multi_add_handle',
		'curl_multi_exec',
		'curl_multi_init',
	],
	static function( $function_name ) {
		return ! function_exists( $function_name );
	}
);
