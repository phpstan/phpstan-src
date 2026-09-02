<?php

declare(strict_types=1);

namespace ResultCacheE2E\Dependency;

use PHPStan\Analyser\ResultCache\ResultCacheDependencyExtension;

final class DuplicateResultCacheDependencyExtension implements ResultCacheDependencyExtension
{
	public function getKey(): string
	{
		return ConfigTypeRegistry::class;
	}

	public function getHash(string $dependencyKey): string
	{
		return 'duplicate';
	}
}
