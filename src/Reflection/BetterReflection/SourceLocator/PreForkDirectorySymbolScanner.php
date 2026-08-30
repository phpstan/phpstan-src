<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\TurboExtensionEnabler;
use function array_merge;
use function array_unique;
use function is_dir;

/**
 * Scans the Composer classmap directories once in the main process, just
 * before it forks its workers, so that every worker inherits the finished
 * symbol indexes instead of building its own.
 *
 * With the turbo extension the scan is native and no longer worth caching
 * (see OptimizedDirectorySourceLocatorFactory), which removes the disk cache,
 * the scan lock and the arena records that used to keep parallel workers from
 * duplicating the work. Forking replaces all three: the memoized locators in
 * OptimizedDirectorySourceLocatorRepository are copy-on-write shared with
 * every child.
 *
 * This adds no work that was not already being done. Both sets of directory
 * locators - the analysed and scanned directories, and the Composer classmap
 * paths that ComposerJsonAndInstalledJsonSourceLocatorMaker::create() builds
 * eagerly - are already built by every worker, so doing it here turns N scans
 * into one. PSR-4 and PSR-0 autoloading resolves a class name straight to a
 * file and never scans a directory, so nothing else is pulled forward.
 *
 * The directory set mirrors BetterReflectionSourceLocatorFactory's. Drifting
 * out of sync with it costs performance, never correctness: a directory
 * missed here is simply scanned by the worker that needs it, and one warmed
 * needlessly is a scan nobody reads.
 *
 * Only the locator construction is hoisted: no bootstrap file runs here, no
 * autoloader is consulted and no file is analysed, so none of the per-worker
 * state that has to stay per-worker is created in the parent.

 */
#[AutowiredService]
final class PreForkDirectorySymbolScanner
{

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $scanDirectories
	 */
	public function __construct(
		private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		private OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		private OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private array $scanDirectories,
	)
	{
	}

	public function scanBeforeFork(): void
	{
		if (!TurboExtensionEnabler::isActive()) {
			// without the extension the cache and the scan lock are still in
			// place and already keep the workers from duplicating the scan
			return;
		}

		$directories = [];
		foreach (array_merge($this->analysedPaths, $this->analysedPathsFromConfig) as $analysedPath) {
			if (!is_dir($analysedPath)) {
				continue;
			}

			$directories[] = $analysedPath;
		}

		// Collect every directory first and scan them in one go. A file that
		// two directories both reach is then read once instead of twice, and
		// the scan pays its per-call costs once instead of per directory:
		// measured over this repository's tree, 0.32s -> 0.16s.
		$this->optimizedDirectorySourceLocatorFactory->beginBatchedScan();

		try {
			foreach (array_unique(array_merge($directories, $this->scanDirectories)) as $directory) {
				$this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
			}

			foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
				// the aggregate locator is thrown away - what matters is that the
				// directory locators it builds land in the repository's memo,
				// which the forked children inherit
				$this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
			}

			$this->optimizedDirectorySourceLocatorFactory->flushBatchedScan();
		} finally {
			// a throw must not leave the factory collecting into a batch that
			// nobody will flush
			$this->optimizedDirectorySourceLocatorFactory->flushBatchedScan();
		}
	}

}
