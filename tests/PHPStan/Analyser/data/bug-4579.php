<?php

namespace Bug4579;

use function PHPStan\Analyser\assertType;

function (string $class): void {
	$foo = new $class();
	assertType('mixed~string', $foo);
	if (method_exists($foo, 'doFoo')) {
		assertType('object&hasMethod(doFoo)', $foo);
	}
};
