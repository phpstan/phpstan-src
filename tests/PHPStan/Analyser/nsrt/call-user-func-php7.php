<?php // lint < 8.0

namespace CallUserFuncPhp7;

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
		assertType('list<string>', call_user_func('CallUserFuncPhp7\generic', $params));
	}
}
