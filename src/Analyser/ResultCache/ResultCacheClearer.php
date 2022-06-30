<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Symfony\Component\Finder\Finder;
use function dirname;
use function unlink;

class ResultCacheClearer
{

	public function __construct(private string $cacheFilePath, private string $tempResultCachePath)
	{
	}

	public function clear(): string
	{
		$dir = dirname($this->cacheFilePath);

		$finder = new Finder();
		foreach ($finder->files()->depth(0)->name('resultCache*.php')->in($dir) as $resultCacheFile) {
			@unlink($resultCacheFile->getPathname());
		}

		return $dir;
	}

	public function clearTemporaryCaches(): void
	{
		$finder = new Finder();
		foreach ($finder->files()->name('*.php')->in($this->tempResultCachePath) as $tmpResultCacheFile) {
			@unlink($tmpResultCacheFile->getPathname());
		}
	}

}
