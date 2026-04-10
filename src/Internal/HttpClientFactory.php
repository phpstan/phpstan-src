<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use GuzzleHttp\Client;
use PHPStan\DependencyInjection\AutowiredService;
use function extension_loaded;

#[AutowiredService]
final class HttpClientFactory
{

	/**
	 * @param array<mixed> $defaults
	 *
	 * @see \GuzzleHttp\RequestOptions
	 */
	public function __construct(private readonly array $defaults = [])
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

		return new Client($config + $this->defaults);
	}

}
