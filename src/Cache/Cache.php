<?php declare(strict_types = 1);

namespace PHPStan\Cache;

final class Cache
{

	public function __construct(private CacheStorage $storage)
	{
	}

	/**
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		return $this->storage->load($key, $variableKey);
	}

	/**
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		$this->storage->save($key, $variableKey, $data);
	}

}
