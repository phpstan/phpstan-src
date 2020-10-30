<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Symfony\Component\Finder\Finder;

class ResultCacheClearer
{

	private string $resultCachePath;

	private string $tempResultCachePath;

	public function __construct(string $resultCachePath, string $tempResultCachePath)
	{
		$this->resultCachePath = $resultCachePath;
		$this->tempResultCachePath = $tempResultCachePath;
	}

	public function clear(): string
	{
		$finder = new Finder();
		foreach ($finder->files()->name('resultCache*.php')->in($this->tempResultCachePath) as $tmpResultCacheFile) {
			@unlink($tmpResultCacheFile->getPathname());
		}

		return $this->resultCachePath;
	}

	public function clearTemporaryCaches(): void
	{
		$finder = new Finder();
		foreach ($finder->files()->name('*.php')->in($this->tempResultCachePath) as $tmpResultCacheFile) {
			@unlink($tmpResultCacheFile->getPathname());
		}
	}

}
