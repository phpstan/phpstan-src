<?php declare(strict_types = 1);

namespace Bug14472;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param string[] $replacement
	 */
	public function arraySpliceOnConstantArrayWithIntKeys(array $replacement): void
	{
		$headers = [
			'last_name',
			'first_name',
			'email',
			'phone',
			'position',
			'client_identifier',
			'profile',
			'hierarchy_role',
			'hierarchy_role_name',
			'state',
			'admin',
			'confirmed',
			'invited',
			'using_native_app',
			'using_sso',
			'last_connection',
		];

		assertType("array{'last_name', 'first_name', 'email', 'phone', 'position', 'client_identifier', 'profile', 'hierarchy_role', 'hierarchy_role_name', 'state', 'admin', 'confirmed', 'invited', 'using_native_app', 'using_sso', 'last_connection'}", $headers);

		array_splice($headers, 9, 0, $replacement);
		assertType("non-empty-list<string>", $headers);
	}

	/**
	 * @param list<string> $replacement
	 */
	public function arraySpliceOnConstantArrayWithIntKeysListReplacement(array $replacement): void
	{
		$headers = ['a', 'b', 'c'];
		array_splice($headers, 1, 0, $replacement);
		assertType("non-empty-list<string>", $headers);
	}

	public function arraySpliceOnConstantArrayWithStringKeys(): void
	{
		$headers = ['a' => 'x', 'b' => 'y', 'c' => 'z'];
		array_splice($headers, 1, 1, ['replacement']);
		assertType("array{a: 'x', 0: 'replacement', c: 'z'}", $headers);
	}
}
