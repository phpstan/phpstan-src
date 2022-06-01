<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\Internal\ComposerHelper;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function count;
use function dirname;
use function is_dir;
use function is_file;

class ComposerJsonAndInstalledJsonSourceLocatorMaker
{

	public function __construct(
		private OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory,
		private OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory,
	)
	{
	}

	public function create(string $projectInstallationPath): ?SourceLocator
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

		$installedJsonDirectoryPath = dirname($installedJsonPath);

		try {
			$installedJsonContents = FileReader::read($installedJsonPath);
			$installedJson = Json::decode($installedJsonContents, Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return null;
		}

		$installed = $installedJson['packages'] ?? $installedJson;
		$dev = (bool) ($installedJson['dev'] ?? true);

		$classMapPaths = array_merge(
			$this->prefixPaths($this->packageToClassMapPaths($composer), $projectInstallationPath . '/'),
			$dev ? $this->prefixPaths($this->packageToClassMapPaths($composer, 'autoload-dev'), $projectInstallationPath . '/') : [],
			...array_map(fn (array $package): array => $this->prefixPaths(
				$this->packageToClassMapPaths($package),
				$this->packagePrefixPath($installedJsonDirectoryPath, $package, $vendorDirectory),
			), $installed),
		);
		$classMapFiles = array_filter($classMapPaths, 'is_file');
		$classMapDirectories = array_filter($classMapPaths, 'is_dir');
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

		foreach ($classMapDirectories as $classMapDirectory) {
			if (!is_dir($classMapDirectory)) {
				continue;
			}
			$locators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($classMapDirectory);
		}

		$files = [];

		foreach (array_merge($classMapFiles, $filePaths) as $file) {
			if (!is_file($file)) {
				continue;
			}
			$files[] = $file;
		}

		if (count($files) > 0) {
			$locators[] = $this->optimizedDirectorySourceLocatorFactory->createByFiles($files);
		}

		return new AggregateSourceLocator($locators);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr4AutoloadNamespaces(array $package, string $autoloadSection = 'autoload'): array
	{
		return array_map(static fn ($namespacePaths): array => (array) $namespacePaths, $package[$autoloadSection]['psr-4'] ?? []);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr0AutoloadNamespaces(array $package, string $autoloadSection = 'autoload'): array
	{
		return array_map(static fn ($namespacePaths): array => (array) $namespacePaths, $package[$autoloadSection]['psr-0'] ?? []);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToClassMapPaths(array $package, string $autoloadSection = 'autoload'): array
	{
		return $package[$autoloadSection]['classmap'] ?? [];
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToFilePaths(array $package, string $autoloadSection = 'autoload'): array
	{
		return $package[$autoloadSection]['files'] ?? [];
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

}
