<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use GuzzleHttp\Client;
use function extension_loaded;

final class HttpClientFactory
{

	/**
	 * @param array<mixed> $config
	 *
	 * @see \GuzzleHttp\RequestOptions
	 */
	public static function createClient(array $config): Client
	{
		if (
			!isset($config['headers']['Accept-Encoding'])
			&& extension_loaded('zlib')
		) {
			$config['headers']['Accept-Encoding'] = 'gzip,deflate';
		}

		return new Client($config);
	}

}
