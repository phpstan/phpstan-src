<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Testing\PHPStanTestCase;

class OpenSslCipherMethodsProviderTest extends PHPStanTestCase
{

	public function testHashFollowsTheSupportedCiphers(): void
	{
		$one = $this->createProvider(['aes-128-cbc', 'aes-256-cbc'])->getHash();
		$fewer = $this->createProvider(['aes-128-cbc'])->getHash();

		$this->assertNotSame(
			$one,
			$fewer,
			'A host offering a different set of ciphers must not reuse the cache: openssl_cipher_iv_length() is int for a supported algorithm and false for an unsupported one.',
		);
	}

	public function testHashIgnoresTheOrderTheCiphersAreReportedIn(): void
	{
		$this->assertSame(
			$this->createProvider(['aes-128-cbc', 'aes-256-cbc'])->getHash(),
			$this->createProvider(['aes-256-cbc', 'aes-128-cbc'])->getHash(),
			'Enumeration order is not analysis-relevant and must not invalidate the cache.',
		);
	}

	/**
	 * @param list<string> $supportedCipherMethods
	 */
	private function createProvider(array $supportedCipherMethods): OpenSslCipherMethodsProvider
	{
		return new OpenSslCipherMethodsProvider($supportedCipherMethods);
	}

}
