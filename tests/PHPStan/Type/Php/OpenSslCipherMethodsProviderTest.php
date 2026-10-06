<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Testing\PHPStanTestCase;
use function restore_error_handler;
use function set_error_handler;

class OpenSslCipherMethodsProviderTest extends PHPStanTestCase
{

	public function testValueFollowsTheSupportedCiphers(): void
	{
		$provider = $this->createProvider(['aes-128-cbc', 'aes-256-cbc']);
		$fewer = $this->createProvider(['aes-128-cbc']);

		$this->assertSame('supported', $provider->getValue('aes-256-cbc'));
		$this->assertSame(
			'unsupported',
			$fewer->getValue('aes-256-cbc'),
			'A host offering a different set of ciphers must analyse the calls naming them again: openssl_cipher_iv_length() is int for a supported algorithm and false for an unsupported one.',
		);
		$this->assertSame('supported', $fewer->getValue('aes-128-cbc'));
	}

	public function testIsSupportedCipherMethodTracksTheCipher(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('/project/src/Analysed.php'));
		$provider = $this->createProvider(['aes-128-cbc']);

		$collector->startFile('/project/src/Analysed.php');
		try {
			$this->assertTrue($provider->isSupportedCipherMethod('AES-128-CBC', $scope));
			$this->assertFalse($provider->isSupportedCipherMethod('aes-256-cbc', $scope));
		} finally {
			$dependencies = $collector->finishFile();
		}

		$this->assertSame([
			ValueDependencyCollector::getId(OpenSslCipherMethodsProvider::class, 'aes-128-cbc'),
			ValueDependencyCollector::getId(OpenSslCipherMethodsProvider::class, 'aes-256-cbc'),
		], $dependencies['dependents']['/project/src/Analysed.php']['analysis']);
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
			$value = (new OpenSslCipherMethodsProvider())->getValue('aes-128-cbc');
		} finally {
			restore_error_handler();
		}

		$this->assertSame([], $leaked, 'Probing the runtime for supported ciphers must not emit warnings.');
		$this->assertContains($value, ['supported', 'unsupported']);
	}

	/**
	 * @param list<string> $supportedCipherMethods
	 */
	private function createProvider(array $supportedCipherMethods): OpenSslCipherMethodsProvider
	{
		return new OpenSslCipherMethodsProvider($supportedCipherMethods);
	}

}
