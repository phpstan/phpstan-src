<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Json;
use PHPStan\File\FileReader;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class ComposerJsonAndInstalledJsonSourceLocatorMaker
{

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;

	public function __construct(
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository
	)
	{
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
	}

	public function create(string $projectInstallationPath): ?SourceLocator
	{
		$composerJsonPath = $projectInstallationPath . '/composer.json';
		if (!is_file($composerJsonPath)) {
			return null;
		}
		$installedJsonPath = $projectInstallationPath . '/vendor/composer/installed.json';
		if (!is_file($installedJsonPath)) {
			return null;
		}

		$installedJsonDirectoryPath = dirname($installedJsonPath);

		try {
			$composerJsonContents = FileReader::read($composerJsonPath);
			$composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);
		} catch (\PHPStan\File\CouldNotReadFileException | \Nette\Utils\JsonException $e) {
			return null;
		}

		try {
			$installedJsonContents = FileReader::read($installedJsonPath);
			$installedJson = Json::decode($installedJsonContents, Json::FORCE_ARRAY);
		} catch (\PHPStan\File\CouldNotReadFileException | \Nette\Utils\JsonException $e) {
			return null;
		}

		$installed = $installedJson['packages'] ?? $installedJson;

		$filePaths = array_merge(
			$this->prefixPaths($this->packageToFilePaths($composer), $projectInstallationPath . '/'),
			...array_map(function (array $package) use ($projectInstallationPath, $installedJsonDirectoryPath): array {
				return $this->prefixPaths(
					$this->packageToFilePaths($package),
					$this->packagePrefixPath($projectInstallationPath, $installedJsonDirectoryPath, $package)
				);
			}, $installed)
		);

		$locators = [];
		foreach ($filePaths as $file) {
			if (!is_file($file)) {
				continue;
			}
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($file);
		}

		return new AggregateSourceLocator($locators);
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
	private function packagePrefixPath(
		string $projectInstallationPath,
		string $installedJsonDirectoryPath,
		array $package
	): string
	{
		if (array_key_exists('install-path', $package)) {
			return $installedJsonDirectoryPath . '/' . $package['install-path'] . '/';
		}

		return $projectInstallationPath . '/vendor/' . $package['name'] . '/';
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
