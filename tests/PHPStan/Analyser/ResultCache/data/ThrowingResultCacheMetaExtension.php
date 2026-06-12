<?php declare(strict_types = 1);

namespace PHPStan\Tests\ResultCache;

use Error;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

final class ThrowingResultCacheMetaExtension implements ResultCacheMetaExtension
{

	public function getKey(): string
	{
		return 'throwing-repro';
	}

	public function getHash(): string
	{
		throw new Error('boom from getHash');
	}

}
