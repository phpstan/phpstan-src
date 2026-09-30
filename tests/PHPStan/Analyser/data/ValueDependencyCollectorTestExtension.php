<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ValueDependencyCollectorTest;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;

final class TestValueExtension implements ResultCacheValueExtension
{

	public int $calls = 0;

	public function getValue(string $key): string
	{
		$this->calls++;

		return 'value of ' . $key;
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
