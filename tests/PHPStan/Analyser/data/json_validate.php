<?php

namespace JsonValidate;

use function PHPStan\Testing\assertType;

function doFoo($m): void {
	assertType('bool', json_validate($m));

	if (json_validate($m)) {
		assertType('non-empty-string', $m);
	} else {
		assertType('mixed', $m);
	}
	assertType('mixed', $m);
}

/**
 * @param non-empty-string $nonES
 */
function doBar($nonES): void {
	if (json_validate($nonES)) {
		assertType('non-empty-string', $nonES);
	} else {
		assertType('non-empty-string', $nonES);
	}
	assertType('non-empty-string', $nonES);
}
