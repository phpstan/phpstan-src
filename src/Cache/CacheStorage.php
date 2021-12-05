<?php declare(strict_types = 1);

namespace PHPStan\Cache;

interface CacheStorage
{

	/**
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey);

	/**
	 * @param mixed $data
	 */
	public function save(string $key, string $variableKey, $data): void;

}
