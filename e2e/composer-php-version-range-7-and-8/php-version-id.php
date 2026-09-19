<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange7And8PhpVersionId;

use RuntimeException;
use function PHPStan\Testing\assertType;

// composer.json requires "^7.4 || ^8.0", so the analysed PHP version spans
// PHP 7.4 - PHP 8.6 no matter which PHP version PHPStan itself runs on.
assertType('int<70400, 80699>', PHP_VERSION_ID);

if (PHP_VERSION_ID >= 80000) {
	// an explicit PHP_VERSION_ID check still narrows the range further down
	assertType('int<80000, 80699>', PHP_VERSION_ID);

	try {
		throw new RuntimeException('foo');
	} catch (RuntimeException) {
		// no "Non-capturing catch is supported only on PHP 8.0 and later."
		echo 'failed';
	}
}
