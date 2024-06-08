<?php

declare(strict_types=1);

namespace Bug11147;

use Exception;
use Redis;
use function get_debug_type;
use function sprintf;

class RedisAdapter {
	public static function createConnection(mixed $url): \Redis|NonExistentClass
	{
		return new \Redis();
	}
}

final class RedisFactory
{
	public function __invoke(): Redis
	{
		$connection = RedisAdapter::createConnection($_ENV['REDISCLOUD_URL']);

		if (! $connection instanceof Redis) {
			throw new Exception(
				sprintf('Wrong Redis instance %s expected %s', get_debug_type($connection), Redis::class),
			);
		}

		return $connection;
	}
}
