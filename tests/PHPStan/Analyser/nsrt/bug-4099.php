<?php

namespace Bug4099;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{key: array{inner: mixed}} $arr
	 */
	function arrayHint(array $arr): void
	{
		assertType('array{key: array{inner: mixed}}', $arr);
		assertNativeType('array', $arr);

		if (!array_key_exists('key', $arr)) {
			assertType('*NEVER*', $arr);
			assertNativeType('array', $arr);
			throw new \Exception('no key "key" found.');
		}
		assertType('array{key: array{inner: mixed}}', $arr);
		assertNativeType('array&hasOffset(\'key\')', $arr);
		assertType('array{inner: mixed}', $arr['key']);
		assertNativeType('mixed', $arr['key']);

		if (!array_key_exists('inner', $arr['key'])) {
			assertType('*NEVER*', $arr);
			assertNativeType('array&hasOffset(\'key\')', $arr);
			assertType('*NEVER*', $arr['key']);
			assertNativeType("mixed~hasOffset('inner')", $arr['key']);
			throw new \Exception('need key.inner');
		}

		assertType('array{key: array{inner: mixed}}', $arr);
		assertNativeType('array&hasOffset(\'key\')', $arr);
	}

}
