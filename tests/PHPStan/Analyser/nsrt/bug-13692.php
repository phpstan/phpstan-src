<?php // lint >= 8.0

namespace Bug13692;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		// Known supported cipher should resolve to int
		assertType('int', openssl_cipher_iv_length('aes-128-cbc'));

		// Unknown cipher should resolve to false
		assertType('false', openssl_cipher_iv_length('unknown'));
	}

}
