<?php

namespace FilterVarReturnsNonEmptyString;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param non-empty-string $str */
	public function run(string $str): void
	{
		assertType('non-empty-string', $str);

		$return = filter_var($str, FILTER_DEFAULT);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_SANITIZE_STRING);
		assertType('non-empty-string|false', $return);

		$str2 = '';
		$return = filter_var($str2, FILTER_DEFAULT);
		assertType('string|false', $return);
	}
}
