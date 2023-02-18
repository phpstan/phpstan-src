<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

interface ResultCacheManagerFactory
{

	public function create(): ResultCacheManager;

}
