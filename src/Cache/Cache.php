<?php declare(strict_types = 1);

namespace PHPStan\Cache;

class Cache
{

	private CacheStorage $storage;

	public function __construct(CacheStorage $storage)
	{
		$this->storage = $storage;
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
