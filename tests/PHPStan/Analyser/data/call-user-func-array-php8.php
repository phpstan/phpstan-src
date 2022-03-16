<?php

namespace CallUserFuncArrayPhp8;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $t
 * @return T
 */
function generic($t) {
	return $t;
}

class Foo {
	/**
	 * @param string $params,...
	 */
	function doVariadics(...$params) {
		// because of named arguments support in php8 we have a different return type as in php7
		// see https://phpstan.org/r/58c30346-9568-47ca-82e5-53b2fffda7d0
		assertType('array<int|string, string>', call_user_func('CallUserFuncArrayPhp8\generic', $params));
	}
}
