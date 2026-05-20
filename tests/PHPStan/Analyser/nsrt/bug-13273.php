<?php declare(strict_types = 1);

namespace Bug13273;

use function PHPStan\Testing\assertType;

function test(int $param): void
{
	$local = 'foo';

	$vars = get_defined_vars();
	assertType('true', array_key_exists('param', $vars));
	assertType('true', array_key_exists('local', $vars));
	assertType('false', array_key_exists('nonexistent', $vars));
}
