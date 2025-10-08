<?php // lint >= 8.1

namespace Bug10215;

class CacheManager
{
	public function __construct(private readonly \Redis $redis)
	{
	}

	public function getCachedValue(string $key, callable $callback): int
	{
		if (false !== ($value = $this->redis->get($key))) {
			return (int) $value;
		}
		$callback();
		if (false !== ($value = $this->redis->get($key))) {
			return (int) $value;
		}

		throw new \LogicException('Cache was not filled by callback');
	}
}
