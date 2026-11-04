<?php declare(strict_types = 1);

namespace Bug15393;

function doFoo(\Redis $redis): void
{
	$redis->set('key', 'value', 60);
	$redis->set('key', 'value', ['nx', 'ex' => 60]);
	$redis->set('key', 'value', null);
	$redis->set('key', 'value');
	$redis->set('key', 'value', 'foo');
}

function doBar(\Redis $redis, \RedisCluster $cluster): void
{
	$redis->sInter(['key1', 'key2']);
	$cluster->sInter(['key1', 'key2']);
	$redis->zCount('key', 0, 100);
	$redis->zCount('key', '-inf', '+inf');
}
