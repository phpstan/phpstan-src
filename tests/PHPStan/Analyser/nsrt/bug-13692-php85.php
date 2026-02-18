<?php // lint >= 8.5

namespace Bug13692Php85;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		assertType('int', openssl_cipher_iv_length('aes-128-cbc-cts'));
		assertType('int', openssl_cipher_iv_length('aes-128-cbc'));
		assertType('false', openssl_cipher_iv_length('unknown'));
	}

}
