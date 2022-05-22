<?php declare(strict_types = 1);

namespace Bug7116;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Cache
{
	public function __construct(public int $ttl)
	{
	}
}

#[Cache(ttl: self::CACHE_TTL)]
class HelloWorld
{
	private const CACHE_TTL = 60;
}
