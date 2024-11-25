<?php

namespace HashFunctions;

use function PHPStan\Testing\assertType;

/**
 * @see hash-functions-74.php
 * @see hash-functions-80.php
 */
class HashFunctionTests
{

	public function hash_hmac(): void
	{
		assertType('lowercase-string&non-falsy-string', hash_hmac('md5', 'data', 'key'));
		assertType('non-falsy-string', hash_hmac('md5', 'data', 'key', true));
		assertType('lowercase-string&non-falsy-string', hash_hmac('sha256', 'data', 'key'));
		assertType('non-falsy-string', hash_hmac('sha256', 'data', 'key', true));
	}

	public function hash_hmac_file(string $string): void
	{
		assertType('(lowercase-string&non-falsy-string)|false', hash_hmac_file('md5', 'filename', 'key'));
		assertType('non-falsy-string|false', hash_hmac_file('md5', 'filename', 'key', true));
		assertType('(lowercase-string&non-falsy-string)|false', hash_hmac_file('sha256', 'filename', 'key'));
		assertType('non-falsy-string|false', hash_hmac_file('sha256', 'filename', 'key', true));
		assertType('((lowercase-string&non-falsy-string)|false)', hash_hmac_file($string, 'filename', 'key'));
		assertType('(non-falsy-string|false)', hash_hmac_file($string, 'filename', 'key', true));
	}

	public function hash($mixed): void
	{
		assertType('lowercase-string&non-falsy-string', hash('sha256', 'data', false));
		assertType('non-falsy-string', hash('sha256', 'data', true));
		assertType('lowercase-string&non-falsy-string', hash('md5', $mixed, false));
	}

	public function hash_file(): void
	{
		assertType('(lowercase-string&non-falsy-string)|false', hash_file('sha256', 'filename', false));
		assertType('non-falsy-string|false', hash_file('sha256', 'filename', true));
		assertType('(lowercase-string&non-falsy-string)|false', hash_file('crc32', 'filename'));
		assertType('non-falsy-string|false', hash_file('crc32', 'filename', true));
	}

	public function hash_hkdf(): void
	{
		assertType('non-falsy-string', hash_hkdf('sha256', 'key'));
	}

	public function hash_pbkdf2(): void
	{
		assertType('lowercase-string&non-falsy-string', hash_pbkdf2('sha256', 'password', 'salt', 1000));
		assertType('non-falsy-string', hash_pbkdf2('sha256', 'password', 'salt', 1000, 0, true));
	}

	public function caseSensitive()
	{
		assertType('lowercase-string&non-falsy-string', hash('SHA256', 'data'));
		assertType('non-falsy-string', hash('SHA256', 'data', true));
	}

	public function constantStrings(int $type)
	{
		switch ($type) {
			case 1:
				$algorithm = 'md5';
				break;
			case 2:
				$algorithm = 'sha256';
				break;
			case 3:
				$algorithm = 'sha512';
				break;
			default:
				return;
		}

		assertType('lowercase-string&non-falsy-string', hash_pbkdf2($algorithm, 'password', 'salt', 1000));
		assertType('non-falsy-string', hash_pbkdf2($algorithm, 'password', 'salt', 1000, 0, true));
	}

}
