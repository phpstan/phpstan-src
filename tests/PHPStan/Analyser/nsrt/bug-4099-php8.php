<?php // lint >= 8.0

namespace Bug4099Php8;

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
			assertNativeType("array<mixed~'key', mixed>", $arr);
			throw new \Exception('no key "key" found.');
		}
		assertType('array{key: array{inner: mixed}}', $arr);
		assertNativeType('non-empty-array&hasOffset(\'key\')', $arr);
		assertType('array{inner: mixed}', $arr['key']);
		assertNativeType('mixed', $arr['key']);

		if (!array_key_exists('inner', $arr['key'])) {
			assertType('*NEVER*', $arr);
			assertNativeType('non-empty-array&hasOffset(\'key\')', $arr);
			assertType('*NEVER*', $arr['key']);
			assertNativeType("array<mixed~'inner', mixed>", $arr['key']);
			throw new \Exception('need key.inner');
		}

		assertType('array{key: array{inner: mixed}}', $arr);
		assertNativeType('non-empty-array&hasOffset(\'key\')', $arr);
	}

}
