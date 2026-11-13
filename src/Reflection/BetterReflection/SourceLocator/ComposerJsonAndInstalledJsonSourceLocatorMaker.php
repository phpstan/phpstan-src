<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function array_reverse;
use function array_values;
use function count;
use function dirname;
use function glob;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function realpath;
use function rtrim;
use function scandir;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;

#[AutowiredService]
final class ComposerJsonAndInstalledJsonSourceLocatorMaker
{

	public function __construct(
		private OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory,
		private OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory,
		private PhpVersion $phpVersion,
	)
	{
	}

	/**
	 * With a batch, the directory locators are added to it, and the returned locator cannot be used
	 * before the batch is scanned.
	 */
	public function create(string $projectInstallationPath, ?OptimizedDirectorySourceLocatorBatch $batch = null): ?SourceLocator
	{
		$project = $this->readProject($projectInstallationPath);
		if ($project === null) {
			return null;
		}

		[$composer, $vendorDirectory, $installedJsonDirectoryPath, $installed, $dev] = $project;

		$classMapPaths = array_merge(
			$this->prefixPaths($this->packageToClassMapPaths($composer), $projectInstallationPath . '/'),
			$dev ? $this->prefixPaths($this->packageToClassMapPaths($composer, 'autoload-dev'), $projectInstallationPath . '/') : [],
			...array_map(fn (array $package): array => $this->prefixPaths(
				$this->packageToClassMapPaths($package),
				$this->packagePrefixPath($installedJsonDirectoryPath, $package, $vendorDirectory),
			), $installed),
		);
		$filePaths = array_merge(
			$this->prefixPaths($this->packageToFilePaths($composer), $projectInstallationPath . '/'),
			$dev ? $this->prefixPaths($this->packageToFilePaths($composer, 'autoload-dev'), $projectInstallationPath . '/') : [],
			...array_map(fn (array $package): array => $this->prefixPaths(
				$this->packageToFilePaths($package),
				$this->packagePrefixPath($installedJsonDirectoryPath, $package, $vendorDirectory),
			), $installed),
		);

		$locators = [];
		$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
			Psr4Mapping::fromArrayMappings(array_merge_recursive(
				$this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $projectInstallationPath),
				$dev ? $this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer, 'autoload-dev'), $projectInstallationPath) : [],
				...array_map(fn (array $package): array => $this->prefixWithPackagePath(
					$this->packageToPsr4AutoloadNamespaces($package),
					$installedJsonDirectoryPath,
					$package,
					$vendorDirectory,
				), $installed),
			)),
		);

		$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
			Psr0Mapping::fromArrayMappings(array_merge_recursive(
				$this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $projectInstallationPath),
				$dev ? $this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer, 'autoload-dev'), $projectInstallationPath) : [],
				...array_map(fn (array $package): array => $this->prefixWithPackagePath(
					$this->packageToPsr0AutoloadNamespaces($package),
					$installedJsonDirectoryPath,
					$package,
					$vendorDirectory,
				), $installed),
			)),
		);

		$files = [];
		$directoryLocators = [];
		foreach ($classMapPaths as $classMapPath) {
			if (is_dir($classMapPath)) {
				$directoryLocators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($classMapPath, $batch);
				continue;
			}
			if (!is_file($classMapPath)) {
				continue;
			}
			$files[] = $classMapPath;
		}
		foreach ($filePaths as $file) {
			if (!is_file($file)) {
				continue;
			}
			$files[] = $file;
		}

		if (count($files) > 0) {
			$directoryLocators[] = $batch !== null
				? $batch->createByFiles($files, 'odsl-installed-files')
				: $this->optimizedDirectorySourceLocatorFactory->createByFiles($files, 'odsl-installed-files');
		}
		if ($directoryLocators !== []) {
			$locators[] = OptimizedDirectorySourceLocatorGroup::create($directoryLocators);
		}

		$binDir = ComposerHelper::getBinDirFromComposerConfig($projectInstallationPath, $composer);
		$phpunitBridgeDir = $binDir . '/.phpunit';
		if (!is_dir($vendorDirectory . '/phpunit/phpunit') && is_dir($phpunitBridgeDir)) {
			// from https://github.com/composer/composer/blob/8ff237afb61b8766efa576b8ae1cc8560c8aed96/phpstan/locate-phpunit-autoloader.php
			$bestDirFound = null;
			$phpunitBridgeDirectories = glob($phpunitBridgeDir . '/phpunit-*', GLOB_ONLYDIR);
			if ($phpunitBridgeDirectories !== false) {
				foreach (array_reverse($phpunitBridgeDirectories) as $dir) {
					$bestDirFound = $dir;
					if ($this->phpVersion->getVersionId() >= 80100 && str_contains($dir, 'phpunit-10')) {
						break;
					}
					if ($this->phpVersion->getVersionId() >= 80000) {
						if (str_contains($dir, 'phpunit-9')) {
							break;
						}
						continue;
					}

					if (str_contains($dir, 'phpunit-8') || str_contains($dir, 'phpunit-7')) {
						break;
					}
				}

				if ($bestDirFound !== null) {
					$phpunitBridgeLocator = $this->create($bestDirFound);
					if ($phpunitBridgeLocator !== null) {
						$locators[] = $phpunitBridgeLocator;
					}
				}
			}
		}

		return new AggregateSourceLocator($locators);
	}

	/**
	 * The existing directories and files of the PSR-4, PSR-0, classmap and files entries of the
	 * project and of the packages in $packageNames - where the project's own code that create()
	 * finds lives. An entry containing the vendor directory, like a PSR-4 namespace mapped to the
	 * project root, is left out: it would bring all the installed packages with it.
	 *
	 * @param array<string, true> $packageNames
	 * @return list<string>
	 */
	public function getAutoloadPaths(string $projectInstallationPath, array $packageNames): array
	{
		$project = $this->readProject($projectInstallationPath);
		if ($project === null) {
			return [];
		}

		[$composer, $vendorDirectory, $installedJsonDirectoryPath, $installed, $dev] = $project;

		// the project's own entries can point into the vendor directory too, like a files entry
		// requiring a file of an installed package - that one changes only through Composer, and is
		// tracked with its package
		$packages = [[$composer, $projectInstallationPath . '/', $dev ? ['autoload', 'autoload-dev'] : ['autoload'], true]];
		foreach ($installed as $package) {
			if (!isset($packageNames[$package['name'] ?? ''])) {
				continue;
			}

			$packages[] = [$package, $this->packagePrefixPath($installedJsonDirectoryPath, $package, $vendorDirectory), ['autoload'], false];
		}

		$realVendorDirectory = realpath($vendorDirectory);
		$paths = [];
		foreach ($packages as [$package, $prefix, $sections, $skipVendorPaths]) {
			foreach ($sections as $section) {
				$sectionPaths = $this->prefixPaths(array_merge(
					$this->packageToClassMapPaths($package, $section),
					$this->packageToFilePaths($package, $section),
					...array_values($this->packageToPsr4AutoloadNamespaces($package, $section)),
					...array_values($this->packageToPsr0AutoloadNamespaces($package, $section)),
				), $prefix);
				foreach ($sectionPaths as $path) {
					$realPath = realpath($path);
					if (
						$skipVendorPaths
						&& $realPath !== false
						&& $realVendorDirectory !== false
						&& str_starts_with($realPath, $realVendorDirectory . DIRECTORY_SEPARATOR)
					) {
						continue;
					}

					foreach ($this->withoutVendorDirectory($path, $realVendorDirectory) as $pathWithoutVendor) {
						$paths[] = $pathWithoutVendor;
					}
				}
			}
		}

		return $paths;
	}

	/**
	 * The path itself, or when it is a directory containing the vendor directory, like a PSR-4
	 * namespace mapped to the project root, its entries other than the vendor directory and the
	 * hidden ones. The files are named the way the source locators name them - through the vendor
	 * directory for a package from a path repository, not where its symlink points.
	 *
	 * @return list<string>
	 */
	private function withoutVendorDirectory(string $path, string|false $realVendorDirectory): array
	{
		$realPath = realpath($path);
		if ($realPath === false) {
			return [];
		}

		$realPath = rtrim($realPath, '/\\');
		if ($realVendorDirectory === false || !str_starts_with($realVendorDirectory . DIRECTORY_SEPARATOR, $realPath . DIRECTORY_SEPARATOR)) {
			return [$path];
		}

		$entries = scandir($realPath);
		if ($entries === false) {
			return [];
		}

		$paths = [];
		foreach ($entries as $entry) {
			if (str_starts_with($entry, '.')) {
				continue;
			}

			$entryPath = $realPath . DIRECTORY_SEPARATOR . $entry;
			if ($entryPath === $realVendorDirectory) {
				continue;
			}
			// a file given by itself is taken whatever its extension, unlike one found in a directory
			if (!is_dir($entryPath) && !str_ends_with($entry, '.php')) {
				continue;
			}

			foreach ($this->withoutVendorDirectory($entryPath, $realVendorDirectory) as $entryPathWithoutVendor) {
				$paths[] = $entryPathWithoutVendor;
			}
		}

		return $paths;
	}

	/**
	 * @return array{mixed[], string, string, list<mixed[]>, bool}|null composer.json, the vendor directory, the directory of installed.json, the installed packages, whether the dev ones are
	 */
	private function readProject(string $projectInstallationPath): ?array
	{
		$composer = ComposerHelper::getComposerConfig($projectInstallationPath);

		if ($composer === null) {
			return null;
		}

		$vendorDirectory = ComposerHelper::getVendorDirFromComposerConfig($projectInstallationPath, $composer);

		$installedJsonPath = $vendorDirectory . '/composer/installed.json';
		if (!is_file($installedJsonPath)) {
			return null;
		}

		try {
			$installedJsonContents = FileReader::read($installedJsonPath);
			$installedJson = Json::decode($installedJsonContents, Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return null;
		}

		return [
			$composer,
			$vendorDirectory,
			dirname($installedJsonPath),
			$installedJson['packages'] ?? $installedJson,
			(bool) ($installedJson['dev'] ?? true),
		];
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr4AutoloadNamespaces(array $package, string $autoloadSection = 'autoload'): array
	{
		$psr4 = $package[$autoloadSection]['psr-4'] ?? [];
		if (!is_array($psr4)) {
			return []; // skip on invalid data
		}
		foreach ($psr4 as $key => $namespacePaths) {
			$stringArray = $this->toStringArray($namespacePaths);
			if (!is_string($key) || $stringArray === null) {
				return []; // skip on invalid data
			}

			$psr4[$key] = $stringArray;
		}
		return $psr4;
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr0AutoloadNamespaces(array $package, string $autoloadSection = 'autoload'): array
	{
		$psr0 = $package[$autoloadSection]['psr-0'] ?? [];
		if (!is_array($psr0)) {
			return []; // skip on invalid data
		}
		foreach ($psr0 as $key => $namespacePaths) {
			$stringArray = $this->toStringArray($namespacePaths);
			if (!is_string($key) || $stringArray === null) {
				return []; // skip on invalid data
			}

			$psr0[$key] = $stringArray;
		}
		return $psr0;
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToClassMapPaths(array $package, string $autoloadSection = 'autoload'): array
	{
		return $this->toStringArray($package[$autoloadSection]['classmap'] ?? []) ?? [];
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToFilePaths(array $package, string $autoloadSection = 'autoload'): array
	{
		return $this->toStringArray($package[$autoloadSection]['files'] ?? []) ?? [];
	}

	/**
	 * @param mixed[] $package
	 */
	private function packagePrefixPath(
		string $installedJsonDirectoryPath,
		array $package,
		string $vendorDirectory,
	): string
	{
		if (array_key_exists('install-path', $package)) {
			return $installedJsonDirectoryPath . '/' . $package['install-path'] . '/';
		}

		return $vendorDirectory . '/' . $package['name'] . '/';
	}

	/**
	 * @param array<string, array<int, string>> $paths
	 * @param array<string, array<int, string>> $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function prefixWithPackagePath(array $paths, string $installedJsonDirectoryPath, array $package, string $vendorDirectory): array
	{
		$prefix = $this->packagePrefixPath($installedJsonDirectoryPath, $package, $vendorDirectory);

		return array_map(fn (array $paths): array => $this->prefixPaths($paths, $prefix), $paths);
	}

	/**
	 * @param array<int|string, array<string>> $paths
	 *
	 * @return array<int|string, array<string>>
	 */
	private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath): array
	{
		return array_map(fn (array $paths): array => $this->prefixPaths($paths, $trimmedInstallationPath . '/'), $paths);
	}

	/**
	 * @param array<int, string> $paths
	 *
	 * @return array<int, string>
	 */
	private function prefixPaths(array $paths, string $prefix): array
	{
		return array_map(static fn (string $path): string => $prefix . $path, $paths);
	}

	/**
	 * @param array<mixed>|string $stringOrArray
	 * @return array<string>|null
	 */
	private function toStringArray(array|string $stringOrArray): ?array
	{
		if (is_string($stringOrArray)) {
			return (array) $stringOrArray;
		}

		foreach ($stringOrArray as $stringOrArrayItem) {
			if (!is_string($stringOrArrayItem)) {
				return null;
			}
		}
		return $stringOrArray;
	}

}
