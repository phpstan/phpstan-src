<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange85;

use function PHPStan\Testing\assertType;

// composer.json requires "^8.5", so the analysed PHP version spans PHP 8.5 - PHP 8.6.
assertType('int<80500, 80699>', PHP_VERSION_ID);

function iniGet(): void
{
	assertType('string', ini_get('memory_limit'));
	// the max_memory_limit ini setting only exists since PHP 8.5
	assertType('string', ini_get('max_memory_limit'));
}
