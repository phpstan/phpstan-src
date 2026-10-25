<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

final class UnregisteredValueExtension implements ResultCacheValueExtension
{

	public function getValue(string $key): string
	{
		return $key;
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
