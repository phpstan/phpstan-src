<?php

namespace LowercaseStringParseStr;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 */
	public function parse(string $lowercase, string $string): void
	{
		$a = [];
		parse_str($lowercase, $a);

		if (array_key_exists('foo', $a)) {
			$value = $a['foo'];
			if (\is_string($value)) {
				assertType('lowercase-string', $value);
			}
		}

		$b = [];
		parse_str($string, $b);

		if (array_key_exists('foo', $b)) {
			$value = $b['foo'];
			if (\is_string($value)) {
				assertType('string', $value);
			}
		}
	}

}
