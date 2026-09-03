<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use function array_key_exists;
use function array_keys;
use function array_values;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function realpath;
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

	/** @var array<string, true>|null names of the installed packages that came from a path repository */
	private ?array $pathPackages = null;

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

	/**
	 * Whether the package was installed from a path repository: Composer symlinked or copied it out
	 * of a directory belonging to the project itself.
	 *
	 * Such a package is edited in place - directly through the symlink, or in its source directory
	 * followed by a reinstall - while its recorded version and reference stay as they are, so the
	 * files of one are tracked one by one like the project's own non-analysed files on top of being
	 * tracked as a package. Everything else in vendor/ changes only through Composer, which always
	 * moves the version or the reference with it.
	 */
	public function isPathPackage(string $package): bool
	{
		return array_key_exists($package, $this->getPathPackages());
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
		$this->resolveInstalledPackages();

		return $this->installPathToPackage;
	}

	/** @return array<string, true> */
	private function getPathPackages(): array
	{
		$this->resolveInstalledPackages();

		return $this->pathPackages;
	}

	/**
	 * Reads what Composer recorded about the installed packages, once: which directory each package
	 * lives in, from installed.php, and which of them came from a path repository, from installed.json
	 * next to it. installed.php does not say - a path package looks there exactly like a downloaded
	 * one - and the two files are read together so the vendor directory is resolved a single time.
	 *
	 * @phpstan-assert !null $this->installPathToPackage
	 * @phpstan-assert !null $this->pathPackages
	 */
	private function resolveInstalledPackages(): void
	{
		if ($this->installPathToPackage !== null && $this->pathPackages !== null) {
			return;
		}

		$map = [];
		$pathPackages = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$composer = ComposerHelper::getComposerConfig($autoloadPath);
			if ($composer === null) {
				continue;
			}

			$composerDirectory = ComposerHelper::getVendorDirFromComposerConfig($autoloadPath, $composer) . '/composer';

			foreach ($this->readPathPackageNames($composerDirectory . '/installed.json') as $pathPackage) {
				$pathPackages[$pathPackage] = true;
			}

			$installedPhp = $composerDirectory . '/installed.php';
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

				$installPath = $this->fileHelper->normalizePath($info['install_path'], '/');
				$map[$installPath] = $package;

				// A package Composer symlinked from a path repository is installed at a link, and a class
				// loaded through it reflects to the directory the link points at - reflection resolves
				// symlinks. Both spellings map to the package, or a file of it would belong to no package
				// at all and a new version of it would go unnoticed.
				$realInstallPath = realpath($info['install_path']);
				if ($realInstallPath === false) {
					continue;
				}

				$realInstallPath = $this->fileHelper->normalizePath($realInstallPath, '/');
				if ($realInstallPath === $installPath) {
					continue;
				}

				$map[$realInstallPath] = $package;
			}
		}

		// Longest install path first, so a package nested under another package's directory matches first.
		uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

		$this->installPathToPackage = $map;
		$this->pathPackages = $pathPackages;
	}

	/**
	 * @return list<string>
	 */
	private function readPathPackageNames(string $installedJson): array
	{
		if (!is_file($installedJson)) {
			return [];
		}

		$contents = file_get_contents($installedJson);
		if ($contents === false) {
			return [];
		}

		try {
			$decoded = Json::decode($contents, Json::FORCE_ARRAY);
		} catch (JsonException) {
			return [];
		}

		if (!is_array($decoded) || !isset($decoded['packages']) || !is_array($decoded['packages'])) {
			return [];
		}

		$names = [];
		foreach ($decoded['packages'] as $package) {
			if (!is_array($package) || !isset($package['name']) || !is_string($package['name'])) {
				continue;
			}
			if (($package['dist']['type'] ?? null) !== 'path') {
				continue;
			}

			$names[] = $package['name'];
		}

		return $names;
	}

}
