<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange80;

use function PHPStan\Testing\assertType;

// composer.json requires "^8.0", so the analysed PHP version spans PHP 8.0 - PHP 8.6.
assertType('int<80000, 80699>', PHP_VERSION_ID);

function variadicParameter(int ...$args): void
{
	// named arguments exist since PHP 8.0, so a variadic parameter is not always a list
	assertType('array<int<0, max>|string, int>', $args);
}
