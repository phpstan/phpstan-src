<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use function array_key_exists;
use function array_keys;
use function array_values;
use function is_array;
use function is_file;
use function is_string;
use function str_starts_with;
use function strlen;
use function uksort;

/**
 * Reasons about the analysed project's Composer packages for result-cache invalidation:
 * maps an absolute file path to the package that owns it (resolvePackage), and diffs two cache
 * metas to find which packages changed (getChangedComposerPackages).
 *
 * Scoped to the analysed project's vendor directories (composerAutoloaderProjectPaths); PHPStan's own
 * bundled dependencies are tied to the PHPStan version, not the project's composer.lock, so they are
 * deliberately not resolved here.
 */
#[AutowiredService]
final class PackageDependencyResolver
{

	/** @var array<string, string>|null normalized install path => package name, longest path first */
	private ?array $installPathToPackage = null;

	/** @var array<string, string|null> file => resolved package (or null for none) */
	private array $resolvedPackages = [];

	/** @param string[] $composerAutoloaderProjectPaths */
	public function __construct(
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		private FileHelper $fileHelper,
	)
	{
	}

	public function resolvePackage(string $file): ?string
	{
		// This is called once per dependency file of every analysed file, so the same vendor files
		// get resolved over and over. Memoize per file: without it this scans every install path
		// (linear in the number of installed packages) on each call, which dominates cold analysis
		// of projects with many dependencies.
		if (array_key_exists($file, $this->resolvedPackages)) {
			return $this->resolvedPackages[$file];
		}

		return $this->resolvedPackages[$file] = $this->doResolvePackage($file);
	}

	private function doResolvePackage(string $file): ?string
	{
		// Normalize with a forward slash regardless of platform: normalizePath() defaults to
		// DIRECTORY_SEPARATOR, so on Windows the paths would use '\' while the prefix check below
		// appends '/', and nothing would ever match.
		$normalizedFile = $this->fileHelper->normalizePath($file, '/');
		foreach ($this->getInstallPathToPackage() as $installPath => $package) {
			if (str_starts_with($normalizedFile, $installPath . '/')) {
				return $package;
			}
		}

		return null;
	}

	/**
	 * Names of packages whose recorded version/reference differs between two result-cache metas, or
	 * null when either meta's composerInstalled cannot be parsed (the caller falls back to a full
	 * re-analysis rather than risk under-invalidation).
	 *
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 * @return list<string>|null
	 */
	public function getChangedComposerPackages(array $cachedMeta, array $currentMeta): ?array
	{
		$cached = $this->extractComposerPackageVersions($cachedMeta['composerInstalled'] ?? null);
		$current = $this->extractComposerPackageVersions($currentMeta['composerInstalled'] ?? null);
		if ($cached === null || $current === null) {
			return null;
		}

		$changed = [];
		foreach ($current as $package => $version) {
			if (array_key_exists($package, $cached) && $cached[$package] === $version) {
				continue;
			}

			$changed[$package] = $package;
		}
		foreach (array_keys($cached) as $package) {
			if (array_key_exists($package, $current)) {
				continue;
			}

			$changed[$package] = $package;
		}

		return array_values($changed);
	}

	/**
	 * @return array<string, string>|null
	 */
	public function extractComposerPackageVersions(mixed $composerInstalled): ?array
	{
		if (!is_array($composerInstalled)) {
			return null;
		}

		$versions = [];
		foreach ($composerInstalled as $installed) {
			if (!is_array($installed) || !isset($installed['versions']) || !is_array($installed['versions'])) {
				return null;
			}
			foreach ($installed['versions'] as $package => $info) {
				if (!is_string($package) || !is_array($info)) {
					return null;
				}
				$reference = $info['reference'] ?? $info['version'] ?? $info['pretty_version'] ?? null;
				$versions[$package] = is_string($reference) ? $reference : '';
			}
		}

		return $versions;
	}

	/** @return array<string, string> */
	private function getInstallPathToPackage(): array
	{
		if ($this->installPathToPackage !== null) {
			return $this->installPathToPackage;
		}

		$map = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$composer = ComposerHelper::getComposerConfig($autoloadPath);
			if ($composer === null) {
				continue;
			}

			$installedPhp = ComposerHelper::getVendorDirFromComposerConfig($autoloadPath, $composer) . '/composer/installed.php';
			if (!is_file($installedPhp)) {
				continue;
			}

			$installed = require $installedPhp;
			if (!is_array($installed) || !isset($installed['versions']) || !is_array($installed['versions'])) {
				continue;
			}

			$root = $installed['root'] ?? null;
			$rootName = is_array($root) && isset($root['name']) && is_string($root['name']) ? $root['name'] : null;

			foreach ($installed['versions'] as $package => $info) {
				if (!is_string($package) || $package === $rootName) {
					continue;
				}
				if (!is_array($info) || !isset($info['install_path']) || !is_string($info['install_path'])) {
					continue;
				}

				$map[$this->fileHelper->normalizePath($info['install_path'], '/')] = $package;
			}
		}

		// Longest install path first, so a package nested under another package's directory matches first.
		uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

		return $this->installPathToPackage = $map;
	}

}
