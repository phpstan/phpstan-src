<?php

namespace FilterVarReturnsNonEmptyString;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param non-empty-string $str
	 * @param string $maybe_empty_string
	 * @param null|string $nullable_string
	 * @param null|non-empty-string $nullable_non_empty_string
	 * @param int $int
	 * @param positive-int $positive_int
	 * @param negative-int $negative_int
	 * @param bool $bool
	 * @param mixed $mixed
	 */
	public function run(
		string $str,
		string $maybe_empty_string,
		?string $nullable_string,
		?string $nullable_non_empty_string,
		int $int,
		int $positive_int,
		int $negative_int,
		bool $bool,
		$mixed,
	): void
	{
		$array = [];
		$object = (object)[];

		assertType('non-empty-string', $str);

		$return = filter_var($str, FILTER_DEFAULT);
		assertType('non-empty-string', $return);

		$return = filter_var($object, FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
		assertType('false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
		assertType('string', $return);

		$return = filter_var($object, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH);
		assertType('false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH);
		assertType('string', $return);

		$return = filter_var($object, FILTER_DEFAULT, FILTER_FLAG_STRIP_BACKTICK);
		assertType('false', $return);

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_STRIP_BACKTICK);
		assertType('string', $return);

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

		$return = filter_var($object, FILTER_SANITIZE_STRING);
		assertType('false', $return);

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

		$return = filter_var($str, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string', $return);

		$return = filter_var($maybe_empty_string, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var('', FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var(true, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'", $return);

		$return = filter_var(false, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var($bool, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var(0.0, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'", $return);

		$return = filter_var(0, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'", $return);

		$return = filter_var(null, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var($nullable_string, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($nullable_non_empty_string, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($array, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false', $return);

		$return = filter_var($object, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false', $return);

		$return = filter_var($this->anyOf($str, $maybe_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, ''), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, true), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string', $return);

		$return = filter_var($this->anyOf($str, false), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, $bool), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string', $return);

		$return = filter_var($this->anyOf($str, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string', $return);

		$return = filter_var($this->anyOf($str, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($str, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false', $return);

		$return = filter_var($this->anyOf($str, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, ''), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, true), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, false), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, $bool), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf($maybe_empty_string, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf('', true), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf('', false), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var($this->anyOf('', $bool), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf('', 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|null", $return);

		$return = filter_var($this->anyOf('', 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|null", $return);

		$return = filter_var($this->anyOf('', null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var($this->anyOf('', $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf('', $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf('', $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf('', $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf(true, false), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf(true, $bool), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf(true, 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|'1'", $return);

		$return = filter_var($this->anyOf(true, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|'1'", $return);

		$return = filter_var($this->anyOf(true, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf(true, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(true, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(true, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|false", $return);

		$return = filter_var($this->anyOf(true, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|false", $return);

		$return = filter_var($this->anyOf(false, $bool), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf(false, 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|null", $return);

		$return = filter_var($this->anyOf(false, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|null", $return);

		$return = filter_var($this->anyOf(false, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('null', $return);

		$return = filter_var($this->anyOf(false, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(false, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(false, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf(false, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf($bool, 0.0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|'1'|null", $return);

		$return = filter_var($this->anyOf($bool, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|'1'|null", $return);

		$return = filter_var($this->anyOf($bool, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|null", $return);

		$return = filter_var($this->anyOf($bool, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($bool, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($bool, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|false|null", $return);

		$return = filter_var($this->anyOf($bool, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'1'|false|null", $return);

		$return = filter_var($this->anyOf(0.0, 0), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'", $return);

		$return = filter_var($this->anyOf(0.0, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|null", $return);

		$return = filter_var($this->anyOf(0.0, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(0.0, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(0.0, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|false", $return);

		$return = filter_var($this->anyOf(0.0, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'-0'|'0'|false", $return);

		$return = filter_var($this->anyOf(0, null), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|null", $return);

		$return = filter_var($this->anyOf(0, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(0, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(0, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|false", $return);

		$return = filter_var($this->anyOf(0, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType("'0'|false", $return);

		$return = filter_var($this->anyOf(null, $nullable_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(null, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf(null, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf(null, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false|null', $return);

		$return = filter_var($this->anyOf($nullable_string, $nullable_non_empty_string), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|null', $return);

		$return = filter_var($this->anyOf($nullable_string, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf($nullable_string, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf($nullable_non_empty_string, $array), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf($nullable_non_empty_string, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);

		$return = filter_var($this->anyOf($array, $object), FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('false', $return);

		$return = filter_var($mixed, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
		assertType('non-empty-string|false|null', $return);
	}

	/**
	 * @template T
	 * @template U
	 * @param T $a
	 * @param U $b
	 * @return T|U
	 */
	private function anyOf($a, $b)
	{
		return random_int(0, 1) ? $a : $b;
	}
}
