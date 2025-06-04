<?php // lint < 8.0

namespace OpensslCipherIvLengthPhp7;

use function PHPStan\Testing\assertType;

class OpensslCipher
{

	/**
	 * @param 'aes-256-cbc'|'aes128'|'aes-128-cbc' $validAlgorithms
	 * @param 'aes-256-cbc'|'invalid' $validAndInvalidAlgorithms
	 */
	public function doFoo(string $s, $validAlgorithms, $validAndInvalidAlgorithms)
	{
		assertType('int|false', openssl_cipher_iv_length('aes-256-cbc'));
		assertType('int|false', openssl_cipher_iv_length('AES-256-CBC'));
		assertType('int|false', openssl_cipher_iv_length('unsupported'));
		assertType('int|false', openssl_cipher_iv_length($s));
		assertType('int|false', openssl_cipher_iv_length($validAlgorithms));
		assertType('int|false', openssl_cipher_iv_length($validAndInvalidAlgorithms));
	}

}
