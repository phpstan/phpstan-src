<?php // lint >= 8.2

namespace OpensslCipherKeyLength;

use function PHPStan\Testing\assertType;

class OpensslCipher
{

	/**
	 * @param 'aes-256-cbc'|'aes128'|'aes-128-cbc' $validAlgorithms
	 * @param 'aes-256-cbc'|'invalid' $validAndInvalidAlgorithms
	 */
	public function doFoo(string $s, $validAlgorithms, $validAndInvalidAlgorithms)
	{
		assertType('int', openssl_cipher_key_length('aes-256-cbc'));
		assertType('int', openssl_cipher_key_length('AES-256-CBC'));
		assertType('false', openssl_cipher_key_length('unsupported'));
		assertType('int|false', openssl_cipher_key_length($s));
		assertType('int', openssl_cipher_key_length($validAlgorithms));
		assertType('int|false', openssl_cipher_key_length($validAndInvalidAlgorithms));
	}

}
