<?php

namespace Bug3132;

use function PHPStan\Testing\assertType;
use const ARRAY_FILTER_USE_BOTH;

class Foo
{

	/**
	 * @param array<string,object> $objects
	 *
	 * @return array<string,object>
	 */
	function filter(array $objects) : array
	{
		return array_filter($objects, static function ($key) {
			assertType('string', $key);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @param array<string,object> $objects
	 *
	 * @return array<string,object>
	 */
	function bar(array $objects) : array
	{
		return array_filter($objects, static function ($val) {
			assertType('object', $val);
		});
	}

	/**
	 * @param array<string,object> $objects
	 *
	 * @return array<string,object>
	 */
	function baz(array $objects) : array
	{
		return array_filter($objects, static function ($val, $key) {
			assertType('string', $key);
			assertType('object', $val);
		}, ARRAY_FILTER_USE_BOTH);
	}

}
