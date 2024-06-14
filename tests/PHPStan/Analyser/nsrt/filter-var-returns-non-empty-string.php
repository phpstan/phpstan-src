<?php

namespace FilterVarReturnsNonEmptyString;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param non-empty-string $str
	 * @param positive-int $positive_int
	 * @param negative-int $negative_int
	 */
	public function run(string $str, int $int, int $positive_int, int $negative_int): void
	{
		assertType('non-empty-string', $str);

		$return = filter_var($str, FILTER_DEFAULT);
		assertType('non-empty-string', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_BACKTICK);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_EMAIL);
		assertType('non-falsy-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_REGEXP);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_URL);
		assertType('non-falsy-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);
		assertType('non-falsy-string|null', $return);

		$return = filter_var($str, FILTER_VALIDATE_IP);
		assertType('non-falsy-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_MAC);
		assertType('non-falsy-string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_DOMAIN);
		assertType('non-empty-string|false', $return);

		$return = filter_var($str, FILTER_SANITIZE_STRING);
		assertType('string|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT);
		assertType('int|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		assertType('int<1, max>|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1], 'flags' => FILTER_NULL_ON_FAILURE]);
		assertType('int<1, max>|null', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['max_range' => 0]]);
		assertType('int<min, 0>|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('int<1, 9>|false', $return);

		$return = filter_var(100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('false', $return);

		$return = filter_var(100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1]]);
		assertType('false', $return);

		$return = filter_var(1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('1', $return);

		$return = filter_var(1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1]]);
		assertType('1', $return);

		$return = filter_var(9, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('9', $return);

		$return = filter_var(1.0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('1', $return);

		$return = filter_var(11.0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
		assertType('false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $positive_int]]);
		assertType('int<1, max>|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => $negative_int, 'max_range' => 0]]);
		assertType('int<min, 0>|false', $return);

		$return = filter_var($str, FILTER_VALIDATE_INT, ['options' => ['min_range' => $int, 'max_range' => $int]]);
		assertType('int|false', $return);

		$str2 = '';
		$return = filter_var($str2, FILTER_DEFAULT);
		assertType("''", $return);

		$return = filter_var($str2, FILTER_VALIDATE_URL);
		assertType('non-falsy-string|false', $return);

		$return = filter_var('foo', FILTER_VALIDATE_INT);
		assertType('false', $return);

		$return = filter_var('foo', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
		assertType('null', $return);

		$return = filter_var('1', FILTER_VALIDATE_INT);
		assertType('1', $return);

		$return = filter_var('0', FILTER_VALIDATE_INT);
		assertType('0', $return);

		$return = filter_var('-1', FILTER_VALIDATE_INT);
		assertType('-1', $return);

		$return = filter_var('0o10', FILTER_VALIDATE_INT);
		assertType('false', $return);

		$return = filter_var('0o10', FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_OCTAL);
		assertType('8', $return);

		$return = filter_var('0x10', FILTER_VALIDATE_INT);
		assertType('false', $return);

		$return = filter_var('0x10', FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX);
		assertType('16', $return);
	}
}
