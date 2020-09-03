<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

interface ResultCacheManagerFactory
{

	/**
	 * @param array<string, string> $fileReplacements
	 * @return ResultCacheManager
	 */
	public function create(array $fileReplacements): ResultCacheManager;

}
