<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Testing\PHPStanTestCase;
use function restore_error_handler;
use function set_error_handler;

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
	 * Reading the ciphers out of the runtime means probing each one, and on PHP 8.0-8.4
	 * openssl_get_cipher_methods() reports algorithms openssl_cipher_iv_length() rejects with a
	 * warning (php/php-src#19994) - 40 of 248 on PHP 8.4.23. `@` does not settle that: a user error
	 * handler that does not consult error_reporting() is still called for a suppressed diagnostic.
	 * See phpstan/phpstan#15176.
	 *
	 * Vacuous on a PHP where nothing is rejected, which is why the count is not asserted - only that
	 * whatever the probe does stays inside it.
	 */
	public function testProbingTheRuntimeLeaksNoWarningThroughAnUnsuppressedHandler(): void
	{
		$leaked = [];
		set_error_handler(static function (int $errno, string $errstr) use (&$leaked): bool {
			// deliberately does not check error_reporting(), so the @ operator does not hide anything
			$leaked[] = $errstr;

			return true;
		});

		try {
			$hash = (new OpenSslCipherMethodsProvider())->getHash();
		} finally {
			restore_error_handler();
		}

		$this->assertSame([], $leaked, 'Probing the runtime for supported ciphers must not emit warnings.');
		$this->assertNotSame('', $hash);
	}

	/**
	 * @param list<string> $supportedCipherMethods
	 */
	private function createProvider(array $supportedCipherMethods): OpenSslCipherMethodsProvider
	{
		return new OpenSslCipherMethodsProvider($supportedCipherMethods);
	}

}
