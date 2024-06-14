<?php // lint < 8.0

namespace HashFunctions;

use function PHPStan\Testing\assertType;

class HashFunctionTests74
{

	public function hash_hmac(string $string): void
	{
		assertType('false', hash_hmac('crc32', 'data', 'key'));
		assertType('false', hash_hmac('invalid', 'data', 'key'));
		assertType('(non-empty-string|false)', hash_hmac($string, 'data', 'key'));
	}

	public function hash_hmac_file(): void
	{
		assertType('false', hash_hmac_file('crc32', 'filename', 'key'));
		assertType('false', hash_hmac_file('invalid', 'filename', 'key'));
	}

	public function hash(string $string): void
	{
		assertType('false', hash('invalid', 'data', false));
		assertType('(non-empty-string|false)', hash($string, 'data'));
	}

	public function hash_file(): void
	{
		assertType('false', hash_file('invalid', 'filename', false));
	}

	public function hash_hkdf(string $string): void
	{
		assertType('false', hash_hkdf('crc32', 'key'));
		assertType('false', hash_hkdf('invalid', 'key'));
		assertType('(non-empty-string|false)', hash_hkdf($string, 'key'));
	}

	public function hash_pbkdf2(string $string): void
	{
		assertType('false', hash_pbkdf2('crc32', 'password', 'salt', 1000));
		assertType('false', hash_pbkdf2('invalid', 'password', 'salt', 1000));
		assertType('(non-empty-string|false)', hash_pbkdf2($string, 'password', 'salt', 1000));
	}

	public function caseSensitive()
	{
		assertType('false', hash_hkdf('CRC32', 'key'));
	}

}
