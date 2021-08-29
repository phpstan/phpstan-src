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

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_BACKTICK);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_EMAIL);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_REGEXP);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_URL);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);
		assertType('non-empty-string|null', $return);

		$return = filter_var($str, FILTER_VALIDATE_IP);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_MAC);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_DOMAIN);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_SANITIZE_STRING);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT);
		assertType('int|false', $return);

		$str2 = '';
		$return = filter_var($str2, FILTER_DEFAULT);
		assertType('string|false', $return);

		$return = filter_var($str2, FILTER_VALIDATE_URL);
		assertType('string|false', $return);

		$str2 = 'foo';
		$return = filter_var($str2, FILTER_VALIDATE_INT);
		assertType('int|false', $return);
	}
}
