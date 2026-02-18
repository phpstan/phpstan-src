<?php // lint >= 8.0

namespace Bug13692;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		// aes-128-cbc-cts is reported by openssl_get_cipher_methods() on PHP 8.0-8.4
		// but is not actually supported by openssl_cipher_iv_length() due to a PHP bug
		// https://github.com/php/php-src/issues/19994
		// On PHP 8.4 where PHPStan runs, this should be refined to false
		// (not incorrectly refined to int)
		assertType('false', openssl_cipher_iv_length('aes-128-cbc-cts'));

		// These should still work correctly
		assertType('int', openssl_cipher_iv_length('aes-128-cbc'));
		assertType('false', openssl_cipher_iv_length('unknown'));
	}

}
