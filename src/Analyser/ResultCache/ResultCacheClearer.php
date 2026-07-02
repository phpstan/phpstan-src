<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function dirname;
use function is_file;
use function str_ends_with;
use function substr;
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

		$basePath = $this->cacheFilePath;
		if (str_ends_with($basePath, '.php')) {
			$basePath = substr($basePath, 0, -4);
		}
		foreach (['errors', 'locallyIgnoredErrors', 'collectedData', 'exportedNodes'] as $section) {
			$sectionFilePath = $basePath . '-' . $section . '.dat';
			if (!is_file($sectionFilePath)) {
				continue;
			}

			@unlink($sectionFilePath);
		}

		if (!is_file($this->cacheFilePath)) {
			return $dir;
		}

		@unlink($this->cacheFilePath);

		return $dir;
	}

}
