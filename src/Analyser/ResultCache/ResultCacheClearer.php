<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function dirname;
use function is_file;
use function unlink;

#[AutowiredService]
final class ResultCacheClearer
{

	public function __construct(
		#[AutowiredParameter(ref: '%resultCachePath%')]
		private string $cacheFilePath,
	)
	{
	}

	public function clear(): string
	{
		$dir = dirname($this->cacheFilePath);
		if (!is_file($this->cacheFilePath)) {
			return $dir;
		}

		@unlink($this->cacheFilePath);

		return $dir;
	}

}
