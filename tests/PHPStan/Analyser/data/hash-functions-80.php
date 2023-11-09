<?php

namespace HashFunctions;

use function PHPStan\Testing\assertType;

class HashFunctionTests80
{

	public function hash_hmac(string $string): void
	{
		assertType('*NEVER*', hash_hmac('crc32', 'data', 'key'));
		assertType('*NEVER*', hash_hmac('invalid', 'data', 'key'));
		assertType('non-empty-string', hash_hmac($string, 'data', 'key'));
	}

	public function hash_hmac_file(): void
	{
		assertType('*NEVER*', hash_hmac_file('crc32', 'filename', 'key'));
		assertType('*NEVER*', hash_hmac_file('invalid', 'filename', 'key'));
	}

	public function hash(string $string): void
	{
		assertType('*NEVER*', hash('invalid', 'data', false));
		assertType('non-falsy-string', hash($string, 'data'));
	}

	public function hash_file(): void
	{
		assertType('*NEVER*', hash_file('invalid', 'filename', false));
	}

	public function hash_hkdf(string $string): void
	{
		assertType('*NEVER*', hash_hkdf('crc32', 'key'));
		assertType('*NEVER*', hash_hkdf('invalid', 'key'));
		assertType('non-falsy-string', hash_hkdf($string, 'key'));
	}

	public function hash_pbkdf2(string $string): void
	{
		assertType('*NEVER*', hash_pbkdf2('crc32', 'password', 'salt', 1000));
		assertType('*NEVER*', hash_pbkdf2('invalid', 'password', 'salt', 1000));
		assertType('non-empty-string', hash_pbkdf2($string, 'password', 'salt', 1000));
	}

	public function caseSensitive()
	{
		assertType('*NEVER*', hash_hkdf('CRC32', 'key'));
	}

}
