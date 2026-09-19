<?php declare(strict_types = 1);

namespace ComposerPhpVersionRangeIniGet;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	assertType('string', ini_get('memory_limit'));
	assertType('string', ini_get('max_memory_limit'));
	assertType('int<80500, 80699>', PHP_VERSION_ID);
}
