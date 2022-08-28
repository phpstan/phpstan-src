<?php declare(strict_types = 1);

namespace PHPStan\ChangelogGenerator;

use Github\Api\RateLimit;
use Github\Api\RateLimit\RateLimitResource;
use Github\Client;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use function sleep;
use function time;

class RateLimitPlugin implements Plugin
{

	private Client $client;

	public function setClient(Client $client): void
	{
		$this->client = $client;
	}

	public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
	{
		$path = $request->getUri()->getPath();
		if ($path === '/rate_limit') {
			return $next($request);
		}

		/** @var RateLimit $api */
		$api = $this->client->api('rate_limit');

		/** @var RateLimitResource $resource */
		$resource = $api->getResource('search');
		if ($resource->getRemaining() < 10) {
			$reset = $resource->getReset();
			$sleepFor = $reset - time();
			if ($sleepFor > 0) {
				sleep($sleepFor);
			}
		}

		return $next($request);
	}

}
