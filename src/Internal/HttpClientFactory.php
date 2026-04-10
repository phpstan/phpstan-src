<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPStan\DependencyInjection\AutowiredService;
use function extension_loaded;

#[AutowiredService]
final class HttpClientFactory
{

	public function __construct(
		private int $timeout = 30,
		private int $connectTimeout = 10,
	)
	{
	}

	/**
	 * @param array<mixed> $config
	 *
	 * @see \GuzzleHttp\RequestOptions
	 */
	public function createClient(array $config): Client
	{
		if (
			!isset($config['headers']['Accept-Encoding'])
			&& extension_loaded('zlib')
		) {
			$config['headers']['Accept-Encoding'] = 'gzip,deflate';
		}

		$defaults = [
			RequestOptions::TIMEOUT => $this->timeout,
			RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout,
		];

		return new Client($config + $defaults);
	}

}
