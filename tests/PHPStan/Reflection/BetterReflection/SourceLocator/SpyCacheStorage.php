<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\CacheStorage;

final class SpyCacheStorage implements CacheStorage
{

	/** @var array<string, mixed> */
	public array $items = [];

	/**
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		return $this->items[$key] ?? null;
	}

	/**
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		$this->items[$key] = $data;
	}

}
