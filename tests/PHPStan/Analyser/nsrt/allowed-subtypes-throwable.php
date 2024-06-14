<?php

namespace AllowedSubtypesThrowable;

use Exception;
use Throwable;
use function PHPStan\Testing\assertType;

function foo(Throwable $throwable): void {
	assertType('Throwable', $throwable);

	if ($throwable instanceof Exception) {
		return;
	}

	assertType('Error', $throwable);
}
