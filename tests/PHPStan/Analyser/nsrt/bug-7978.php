<?php declare(strict_types = 1);

namespace Bug7978;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type TypeArrayCredentialsBasic array{
 *     username          : string,
 *     password          : string,
 * }
 *
 * @phpstan-type TypeArrayCredentialsHeader array{
 *     app_id                : string,
 *     app_key               : string
 * }
 *
 * @phpstan-type TypeArrayCredentials TypeArrayCredentialsBasic|TypeArrayCredentialsHeader
 */
class Test {

	const FIELD_SETS = [
		'basic'   => ['username', 'password'],
		'headers' => ['app_id', 'app_key'],
	];

	public function doSomething(): void
	{
		foreach (self::FIELD_SETS as $type => $fields) {
			$credentials = [];
			foreach ($fields as $field) {
				$credentials[$field] = 'fake';
			}
			assertType("array{app_id: 'fake', app_key: 'fake'}|array{username: 'fake', password: 'fake'}", $credentials);
		}
	}

	/** @param list{'username', 'password'}|list{'app_id', 'app_key'} $fields */
	public function directUnionForeach(array $fields): void
	{
		$credentials = [];
		foreach ($fields as $field) {
			$credentials[$field] = 'fake';
		}
		assertType("array{app_id: 'fake', app_key: 'fake'}|array{username: 'fake', password: 'fake'}", $credentials);
	}

	/** @param list{'a', 'b', 'c'}|list{'x'} $fields */
	public function differentLengthArrays(array $fields): void
	{
		$result = [];
		foreach ($fields as $field) {
			$result[$field] = 1;
		}
		assertType("array{a: 1, b: 1, c: 1}|array{x: 1}", $result);
	}
}
