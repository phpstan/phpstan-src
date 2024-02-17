<?php

namespace Bug3771Php8;

use function PHPStan\Testing\assertType;

/**
 * @param string $algo
 */
function returnTypesForHashHmac($algo) {
	// valid constant
    assertType('non-empty-string', hash_hmac('sha256', 'data', 'key'));

	// invalid constant
	assertType('*NEVER*', hash_hmac('invalid', 'data', 'key'));

	// non-constant
	assertType('non-empty-string', hash_hmac($algo, 'data', 'key'));
}
