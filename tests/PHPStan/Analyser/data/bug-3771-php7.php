<?php declare(strict_types = 1);

namespace Bug3771Php7;

use function PHPStan\Testing\assertType;

/**
 * @param string $algo
 */
function returnTypesForHashHmac($algo) {
	// valid constant
    assertType('non-empty-string', hash_hmac('sha256', 'data', 'key'));

	// invalid constant
	assertType('false', hash_hmac('invalid', 'data', 'key'));

	// non-constant
	assertType('non-empty-string|false', hash_hmac($algo, 'data', 'key'));
}
