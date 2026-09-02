<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use PHPStan\DependencyInjection\AutowiredService;
use function array_filter;
use function array_map;
use function array_values;
use function function_exists;
use function hash;
use function implode;
use function in_array;
use function openssl_cipher_iv_length;
use function openssl_get_cipher_methods;
use function sort;
use function strtolower;

#[AutowiredService]
final class OpenSslCipherMethodsProvider implements ResultCacheMetaExtension
{

	/**
	 * @param list<string>|null $supportedCipherMethods Overridable so tests do not depend on the
	 *                                                  OpenSSL the suite happens to run against;
	 *                                                  null means read it from the runtime.
	 */
	public function __construct(
		private ?array $supportedCipherMethods = null,
	)
	{
	}

	/**
	 * Returns supported cipher methods in lowercase.
	 *
	 * Filters out methods that openssl_get_cipher_methods() reports
	 * but are not actually usable due to https://github.com/php/php-src/issues/19994
	 *
	 * @return list<string>
	 */
	private function getSupportedCipherMethods(): array
	{
		if ($this->supportedCipherMethods !== null) {
			return $this->supportedCipherMethods;
		}

		$methods = [];
		if (function_exists('openssl_get_cipher_methods')) {
			// openssl_get_cipher_methods() reports algorithms that are not actually
			// supported on PHP 8.0-8.4 due to https://github.com/php/php-src/issues/19994
			// Filter by actually testing each algorithm with openssl_cipher_iv_length().
			$methods = array_values(array_filter(
				openssl_get_cipher_methods(true),
				static fn (string $algorithm): bool => @openssl_cipher_iv_length($algorithm) !== false,
			));
		}

		$this->supportedCipherMethods = array_map('strtolower', $methods);

		return $this->supportedCipherMethods;
	}

	public function isSupportedCipherMethod(string $method): bool
	{
		return in_array(strtolower($method), $this->getSupportedCipherMethods(), true);
	}

	public function getKey(): string
	{
		return 'openSslCipherMethods';
	}

	/**
	 * The supported ciphers are read out of the runtime, and the inferred type of
	 * openssl_cipher_iv_length() and friends follows them, so a host offering a different set has to
	 * invalidate the cache. The set is a property of the PHP build rather than of the PHP version:
	 * PHP 8.4.25 reports 212 methods on ubuntu-latest and 208 on macos-latest.
	 */
	public function getHash(): string
	{
		$methods = $this->getSupportedCipherMethods();
		sort($methods);

		return hash('sha256', implode(',', $methods));
	}

}
