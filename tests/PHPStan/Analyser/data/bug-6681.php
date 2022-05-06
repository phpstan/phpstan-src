<?php

namespace Bug6681;

class ApiCacheMap
{
	protected const DEFAULT_CACHE_TTL = 600;

	protected const CACHE_MAP = [self::DEFAULT_CACHE_TTL => []];
}



$apiCacheMap = new class extends ApiCacheMap {
	protected const CACHE_MAP = [
		1 => ApiCacheMap::CACHE_MAP[self::DEFAULT_CACHE_TTL],
	];
};
