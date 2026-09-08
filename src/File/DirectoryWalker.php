<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredService;
use Symfony\Component\Finder\Finder;
use function array_key_exists;
use function implode;

/**
 * The raw filesystem walk behind FileFinder.
 *
 * The analyse and the scan FileFinder differ only in their FileExcluder, so an analysis walks the
 * same directories twice: once to build the list of analysed files, and once for the result cache
 * metadata, which records the files that are scanned but not analysed. walkCached() shares that
 * walk between the two - both happen before the analysis starts, on a file set that is snapshotted
 * for the whole run anyway.
 *
 * FileMonitor detects changes by re-running the finder and must see the filesystem as it is now,
 * so it walks uncached and clears the shared walks before each check.
 */
#[AutowiredService]
final class DirectoryWalker
{

	/** @var array<string, list<string>> */
	private array $cachedWalks = [];

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>
	 */
	public function walk(string $directory, array $fileExtensions): array
	{
		$finder = new Finder();
		$finder->followLinks();

		$files = [];
		foreach ($finder->files()->name('*.{' . implode(',', $fileExtensions) . '}')->in($directory) as $fileInfo) {
			$files[] = $fileInfo->getPathname();
		}

		return $files;
	}

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>
	 */
	public function walkCached(string $directory, array $fileExtensions): array
	{
		$key = $directory . "\n" . implode(',', $fileExtensions);
		if (array_key_exists($key, $this->cachedWalks)) {
			return $this->cachedWalks[$key];
		}

		return $this->cachedWalks[$key] = $this->walk($directory, $fileExtensions);
	}

	public function clearCachedWalks(): void
	{
		$this->cachedWalks = [];
	}

}
