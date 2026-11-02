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
 * Finds the installed packages of a Composer project with cache tokens derived
 * from installed.php versions, for the container cache key of
 * AttributeServicesDirectoriesResolver.
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

		$vendorDirectory = rtrim($this->fileHelper->normalizePath(
			ComposerHelper::getVendorDirFromComposerConfig($projectPath, $composer),
			'/',
		), '/');

		$installedJson = $this->loadInstalledJson($vendorDirectory);
		$installedPackages = [];
		if ($installedJson !== null) {
			$installedPackages = $installedJson['packages'] ?? $installedJson;
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
					$this->createCacheToken($package['name'], $installPath, $vendorDirectory, $versions),
				);
			}
		}

		// Longest install path first, so a package nested under another package's directory matches first.
		uksort($packagesByInstallPath, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

		return new ComposerProject($packagesByInstallPath);
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

}
