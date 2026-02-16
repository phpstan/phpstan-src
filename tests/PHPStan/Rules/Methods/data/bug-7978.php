<?php declare(strict_types = 1);

namespace Bug7978;

class Test {

	const FIELD_SETS = [
		'basic'   => ['username', 'password'],
		'headers' => ['app_id', 'app_key'],
	];

	/**
	 * @param array<string, string> $credentials
	 */
	public function acceptCredentials(array $credentials): void
	{
	}

	public function doSomething(): void
	{
		foreach (self::FIELD_SETS as $type => $fields) {
			$credentials = [];
			foreach ($fields as $field) {
				$credentials[$field] = 'fake';
			}
			$this->acceptCredentials($credentials);
		}
	}
}
