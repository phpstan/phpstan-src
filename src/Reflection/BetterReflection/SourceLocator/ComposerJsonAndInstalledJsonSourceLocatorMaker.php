<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Json;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class ComposerJsonAndInstalledJsonSourceLocatorMaker
{

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository */
	private $optimizedDirectorySourceLocatorRepository;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository */
	private $optimizedSingleFileSourceLocatorRepository;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory */
	private $optimizedPsrAutoloaderLocatorFactory;

	public function __construct(
		OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory
	)
	{
		$this->optimizedDirectorySourceLocatorRepository = $optimizedDirectorySourceLocatorRepository;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->optimizedPsrAutoloaderLocatorFactory = $optimizedPsrAutoloaderLocatorFactory;
	}

	public function create(string $installationPath): SourceLocator
	{
		$composerJsonPath = $installationPath . '/composer.json';
		$installedJsonPath = $installationPath . '/vendor/composer/installed.json';

		$composerJsonContents = file_get_contents($composerJsonPath);
		if ($composerJsonContents === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);

		$installedJsonContents = file_get_contents($installedJsonPath);
		if ($installedJsonContents === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$installed = Json::decode($installedJsonContents, Json::FORCE_ARRAY);

		$classMapPaths = array_merge(
			$this->prefixPaths($this->packageToClassMapPaths($composer), $installationPath . '/'),
			...array_map(function (array $package) use ($installationPath): array {
				return $this->prefixPaths(
					$this->packageToClassMapPaths($package),
					$this->packagePrefixPath($installationPath, $package)
				);
			}, $installed)
		);
		$classMapFiles = array_filter($classMapPaths, 'is_file');
		$classMapDirectories = array_filter($classMapPaths, 'is_dir');
		$filePaths = array_merge(
			$this->prefixPaths($this->packageToFilePaths($composer), $installationPath . '/'),
			...array_map(function (array $package) use ($installationPath): array {
				return $this->prefixPaths(
					$this->packageToFilePaths($package),
					$this->packagePrefixPath($installationPath, $package)
				);
			}, $installed)
		);

		$locators = [];
		$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
			Psr4Mapping::fromArrayMappings(array_merge_recursive(
				$this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $installationPath),
				...array_map(function (array $package) use ($installationPath): array {
					return $this->prefixWithPackagePath(
						$this->packageToPsr4AutoloadNamespaces($package),
						$installationPath,
						$package
					);
				}, $installed)
			))
		);

		$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
			Psr0Mapping::fromArrayMappings(array_merge_recursive(
				$this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $installationPath),
				...array_map(function (array $package) use ($installationPath): array {
					return $this->prefixWithPackagePath(
						$this->packageToPsr0AutoloadNamespaces($package),
						$installationPath,
						$package
					);
				}, $installed)
			))
		);

		foreach ($classMapDirectories as $classMapDirectory) {
			$locators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($classMapDirectory);
		}

		foreach (array_merge($classMapFiles, $filePaths) as $file) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($file);
		}

		return new AggregateSourceLocator($locators);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr4AutoloadNamespaces(array $package): array
	{
		return array_map(static function ($namespacePaths): array {
			return (array) $namespacePaths;
		}, $package['autoload']['psr-4'] ?? []);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function packageToPsr0AutoloadNamespaces(array $package): array
	{
		return array_map(static function ($namespacePaths): array {
			return (array) $namespacePaths;
		}, $package['autoload']['psr-0'] ?? []);
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToClassMapPaths(array $package): array
	{
		return $package['autoload']['classmap'] ?? [];
	}

	/**
	 * @param mixed[] $package
	 *
	 * @return array<int, string>
	 */
	private function packageToFilePaths(array $package): array
	{
		return $package['autoload']['files'] ?? [];
	}

	/**
	 * @param mixed[] $package
	 */
	private function packagePrefixPath(string $trimmedInstallationPath, array $package): string
	{
		return $trimmedInstallationPath . '/vendor/' . $package['name'] . '/';
	}

	/**
	 * @param array<string, array<int, string>> $paths
	 * @param array<string, array<int, string>> $package
	 *
	 * @return array<string, array<int, string>>
	 */
	private function prefixWithPackagePath(array $paths, string $trimmedInstallationPath, array $package): array
	{
		$prefix = $this->packagePrefixPath($trimmedInstallationPath, $package);

		return array_map(function (array $paths) use ($prefix): array {
			return $this->prefixPaths($paths, $prefix);
		}, $paths);
	}

	/**
	 * @param array<int|string, array<string>> $paths
	 *
	 * @return array<int|string, array<string>>
	 */
	private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath): array
	{
		return array_map(function (array $paths) use ($trimmedInstallationPath): array {
			return $this->prefixPaths($paths, $trimmedInstallationPath . '/');
		}, $paths);
	}

	/**
	 * @param array<int, string> $paths
	 *
	 * @return array<int, string>
	 */
	private function prefixPaths(array $paths, string $prefix): array
	{
		return array_map(static function (string $path) use ($prefix): string {
			return $prefix . $path;
		}, $paths);
	}

}
