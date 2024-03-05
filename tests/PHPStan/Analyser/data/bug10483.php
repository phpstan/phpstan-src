<?php

namespace Bug10483;

use function PHPStan\Testing\assertType;

function doFoo(): void {
	assertType('non-falsy-string|false', filter_var("no", FILTER_VALIDATE_REGEXP));
}
