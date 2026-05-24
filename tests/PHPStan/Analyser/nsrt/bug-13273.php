<?php declare(strict_types = 1);

namespace Bug13273;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

function test(int $param): void
{
	$local = 'foo';

	$vars = get_defined_vars();
	assertType('true', array_key_exists('param', $vars));
	assertType('true', array_key_exists('local', $vars));
	assertType('false', array_key_exists('nonexistent', $vars));
	assertNativeType('true', array_key_exists('param', $vars));
	assertNativeType('true', array_key_exists('local', $vars));
	assertNativeType('false', array_key_exists('nonexistent', $vars));
}
