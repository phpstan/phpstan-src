<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

final class ParameterValueExtension implements ResultCacheValueExtension
{

	public function getValue(string $key): string
	{
		return Container::getParameter($key) ?? 'missing';
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
