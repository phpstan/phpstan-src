<?php declare(strict_types = 1);

namespace PHPStan\Cache;

interface CacheStorage
{

	/**
	 * @param non-empty-string $key
	 *
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey);

	/**
	 * @param non-empty-string $key
	 *
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void;

}
