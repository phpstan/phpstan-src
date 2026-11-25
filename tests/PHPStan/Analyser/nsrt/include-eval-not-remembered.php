<?php declare(strict_types = 1);

namespace IncludeEvalNotRemembered;

use function PHPStan\Testing\assertType;

function doFoo(string $file, string $code): void
{
	if (include $file) {
		assertType('mixed', include $file);
	}
	if ((include $file) === 'xyz') {
		assertType('mixed', include $file);
	}
	if (eval($code)) {
		assertType('mixed', eval($code));
	}
	if ((eval($code)) === 'xyz') {
		assertType('mixed', eval($code));
	}
}
