<?php declare(strict_types = 1);

namespace PHPStan\Cache;

final class MemoryCacheStorage implements CacheStorage
{

	/** @var array<string, CacheItem> */
	private array $storage = [];

	public function load(string $key, string $variableKey): mixed
	{
		if (!isset($this->storage[$key])) {
			return null;
		}

		$item = $this->storage[$key];
		if (!$item->isVariableKeyValid($variableKey)) {
			return null;
		}

		return $item->getData();
	}

	public function save(string $key, string $variableKey, mixed $data): void
	{
		$this->storage[$key] = new CacheItem($variableKey, $data);
	}

}
