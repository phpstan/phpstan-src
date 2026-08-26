<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Internal\ComposerHelper;
use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function realpath;
use function rtrim;
use function str_starts_with;
use function strlen;
use function uksort;

/**
 * Builds the Composer metadata view AttributeServicesDirectoriesResolver reasons about:
 * root and per-package autoload rules with absolutized paths, the --no-dev flag,
 * and per-package cache tokens derived from installed.php versions.
 */
final class ComposerProjectFactory
{

	public function __construct(private FileHelper $fileHelper)
	{
	}

	public function create(string $projectPath): ?ComposerProject
	{
		$composer = ComposerHelper::getComposerConfig($projectPath);
		if ($composer === null) {
			return null;
		}

		$rootPath = rtrim($this->fileHelper->normalizePath($projectPath, '/'), '/');
		$vendorDirectory = rtrim($this->fileHelper->normalizePath(
			ComposerHelper::getVendorDirFromComposerConfig($projectPath, $composer),
			'/',
		), '/');

		$installedJson = $this->loadInstalledJson($vendorDirectory);
		$installedPackages = [];
		$devInstalled = true;
		if ($installedJson !== null) {
			$installedPackages = $installedJson['packages'] ?? $installedJson;
			$devInstalled = (bool) ($installedJson['dev'] ?? true);
		}

		$versions = $this->loadInstalledVersions($vendorDirectory);

		$packagesByInstallPath = [];
		if (is_array($installedPackages)) {
			foreach ($installedPackages as $package) {
				if (!is_array($package) || !isset($package['name']) || !is_string($package['name'])) {
					continue;
				}

				if (isset($package['install-path']) && is_string($package['install-path'])) {
					$installPath = $vendorDirectory . '/composer/' . $package['install-path'];
				} else {
					$installPath = $vendorDirectory . '/' . $package['name'];
				}
				$installPath = rtrim($this->fileHelper->normalizePath($installPath, '/'), '/');

				$packagesByInstallPath[$installPath] = new ComposerPackage(
					$package['name'],
					$installPath,
					$this->createCacheToken($package['name'], $installPath, $vendorDirectory, $versions),
					$this->extractAutoloadRules($package, 'autoload', $installPath),
				);
			}
		}

		// Longest install path first, so a package nested under another package's directory matches first.
		uksort($packagesByInstallPath, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

		return new ComposerProject(
			$rootPath,
			$vendorDirectory,
			$devInstalled,
			$this->extractAutoloadRules($composer, 'autoload', $rootPath),
			$this->extractAutoloadRules($composer, 'autoload-dev', $rootPath),
			$packagesByInstallPath,
		);
	}

	/**
	 * @return array<mixed>|null
	 */
	private function loadInstalledJson(string $vendorDirectory): ?array
	{
		$installedJsonPath = $vendorDirectory . '/composer/installed.json';
		if (!is_file($installedJsonPath)) {
			return null;
		}

		try {
			$installedJson = Json::decode(FileReader::read($installedJsonPath), Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return null;
		}

		if (!is_array($installedJson)) {
			return null;
		}

		return $installedJson;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function loadInstalledVersions(string $vendorDirectory): array
	{
		$installedPhp = $vendorDirectory . '/composer/installed.php';
		if (!is_file($installedPhp)) {
			return [];
		}

		$installed = require $installedPhp;
		if (!is_array($installed) || !isset($installed['versions']) || !is_array($installed['versions'])) {
			return [];
		}

		$versions = [];
		foreach ($installed['versions'] as $package => $info) {
			if (!is_string($package) || !is_array($info)) {
				continue;
			}

			$versions[$package] = $info;
		}

		return $versions;
	}

	/**
	 * Version identity of the package usable as a container cache key, or null when the installed
	 * files can change without the recorded version changing - a path repository (the install path
	 * escapes the vendor directory or is a symlink) or a missing reference. Null makes the resolver
	 * fall back to hashing the directory contents.
	 *
	 * @param array<string, array<string, mixed>> $versions
	 */
	private function createCacheToken(string $packageName, string $installPath, string $vendorDirectory, array $versions): ?string
	{
		if (!str_starts_with($installPath, $vendorDirectory . '/')) {
			return null;
		}

		$realInstallPath = realpath($installPath);
		if ($realInstallPath === false || rtrim($this->fileHelper->normalizePath($realInstallPath, '/'), '/') !== $installPath) {
			return null;
		}

		$info = $versions[$packageName] ?? null;
		if ($info === null || !isset($info['pretty_version']) || !is_string($info['pretty_version'])) {
			return null;
		}

		if (preg_match('/[^v\d.]/', $info['pretty_version']) === 0) {
			// a tagged version, see ComposerHelper::processPackageVersion()
			return $info['pretty_version'];
		}

		if (isset($info['reference']) && is_string($info['reference']) && $info['reference'] !== '') {
			return $info['pretty_version'] . '@' . $info['reference'];
		}

		return null;
	}

	/**
	 * @param array<mixed> $package
	 */
	private function extractAutoloadRules(array $package, string $autoloadSection, string $basePath): AutoloadRules
	{
		$section = $package[$autoloadSection] ?? [];
		if (!is_array($section)) {
			return AutoloadRules::createEmpty();
		}

		return new AutoloadRules(
			$this->extractPsrRules($section, 'psr-4', $basePath),
			$this->extractPathList($section, 'classmap', $basePath),
			$this->extractPsrRules($section, 'psr-0', $basePath),
			$this->extractPathList($section, 'files', $basePath),
		);
	}

	/**
	 * @param array<mixed> $section
	 * @return array<string, list<string>>
	 */
	private function extractPsrRules(array $section, string $key, string $basePath): array
	{
		$rules = $section[$key] ?? [];
		if (!is_array($rules)) {
			return [];
		}

		$result = [];
		foreach ($rules as $namespacePrefix => $paths) {
			if (!is_string($namespacePrefix)) {
				continue;
			}

			$absolutePaths = [];
			foreach (is_array($paths) ? $paths : [$paths] as $path) {
				if (!is_string($path)) {
					continue;
				}

				$absolutePaths[] = $this->absolutizeRulePath($basePath, $path);
			}

			if ($absolutePaths === []) {
				continue;
			}

			$result[$namespacePrefix] = $absolutePaths;
		}

		return $result;
	}

	/**
	 * @param array<mixed> $section
	 * @return list<string>
	 */
	private function extractPathList(array $section, string $key, string $basePath): array
	{
		$paths = $section[$key] ?? [];
		if (!is_array($paths)) {
			return [];
		}

		$result = [];
		foreach ($paths as $path) {
			if (!is_string($path)) {
				continue;
			}

			$result[] = $this->absolutizeRulePath($basePath, $path);
		}

		return $result;
	}

	private function absolutizeRulePath(string $basePath, string $path): string
	{
		return rtrim($this->fileHelper->normalizePath($basePath . '/' . $path, '/'), '/');
	}

}
