<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Testing\PHPStanTestCase;

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
	 * @param list<string> $supportedCipherMethods
	 */
	private function createProvider(array $supportedCipherMethods): OpenSslCipherMethodsProvider
	{
		return new OpenSslCipherMethodsProvider($supportedCipherMethods);
	}

}
