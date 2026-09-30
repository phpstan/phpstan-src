<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

final class HasServiceValueExtension implements ResultCacheValueExtension
{

	public function getValue(string $key): string
	{
		return Container::getService($key) ?? 'missing';
	}

	public function keyToResultCache(string $key): string
	{
		return $key;
	}

	public function keyFromResultCache(string $storedKey): string
	{
		return $storedKey;
	}

}
