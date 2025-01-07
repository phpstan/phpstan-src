<?php

declare(strict_types=1);

namespace ResultCacheE2E\MetaExtension;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

final class DummyResultCacheMetaExtension implements ResultCacheMetaExtension
{
	public function getKey(): string
	{
		return 'e2e-dummy-result-cache-meta-extension';
	}

	public function getHash(): string
	{
		// @phpstan-ignore argument.type (the file is always present so this won't pass `false` as an argument)
		return trim(file_get_contents(__DIR__ . '/../hash.txt'));
	}
}
