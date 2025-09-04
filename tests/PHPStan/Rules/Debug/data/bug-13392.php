<?php declare(strict_types = 1);

namespace Bug13392;

use RedisCluster;

interface Client
{
	public function get(): RedisCluster;
}

function func(Client $client): void
{
	$redisCluster = $client->get();
	\PHPStan\dumpType($redisCluster);

	$transaction = $redisCluster->multi();
	\PHPStan\dumpType($transaction);	
}
