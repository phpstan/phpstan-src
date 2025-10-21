<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class Cache
{

	public function __construct(
		#[AutowiredParameter(ref: '@cacheStorage')]
		private CacheStorage $storage,
	)
	{
	}

	/**
	 * @param non-empty-string $key
	 *
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		return $this->storage->load($key, $variableKey);
	}

	/**
	 * @param non-empty-string $key
	 *
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		$this->storage->save($key, $variableKey, $data);
	}

}
