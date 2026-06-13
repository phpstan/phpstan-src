<?php

declare(strict_types=1);

namespace ResultCacheE2E\MetaExtensionThrow;

use Error;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

final class ThrowingResultCacheMetaExtension implements ResultCacheMetaExtension
{
	public function getKey(): string
	{
		return 'e2e-throwing-result-cache-meta-extension';
	}

	public function getHash(): string
	{
		throw new Error('boom from getHash');
	}
}
