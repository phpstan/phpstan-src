<?php

use function PHPStan\Testing\assertType;

function doFoo(mixed $filter): void {
	assertType('non-falsy-string|false', filter_var("no", FILTER_VALIDATE_REGEXP));

	// if FILTER_SANITIZE_MAGIC_QUOTES would contain a valid value, the following line would yield "string|false".
	// but since in phpstan bootstrap file the constant was miss-defined with a invalid value, PHPStan does not know the constant and returns "mixed", like for regular unknown constants.
	assertType('mixed', filter_var($filter, FILTER_SANITIZE_MAGIC_QUOTES));
}
