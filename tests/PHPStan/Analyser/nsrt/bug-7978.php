<?php declare(strict_types = 1);

namespace Bug7978;

use function PHPStan\Testing\assertType;

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
			assertType("array{app_id?: 'fake', app_key?: 'fake', password?: 'fake', username?: 'fake'}", $credentials);
		}
	}
}
