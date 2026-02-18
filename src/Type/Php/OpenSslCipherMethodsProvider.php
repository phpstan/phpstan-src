<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function array_filter;
use function array_map;
use function array_values;
use function function_exists;
use function in_array;
use function openssl_cipher_iv_length;
use function openssl_get_cipher_methods;
use function strtolower;

#[AutowiredService]
final class OpenSslCipherMethodsProvider
{

	/** @var list<string>|null */
	private ?array $supportedCipherMethods = null;

	/**
	 * Returns supported cipher methods in lowercase.
	 *
	 * Filters out methods that openssl_get_cipher_methods() reports
	 * but are not actually usable due to https://github.com/php/php-src/issues/19994
	 *
	 * @return list<string>
	 */
	public function getSupportedCipherMethods(): array
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

}
