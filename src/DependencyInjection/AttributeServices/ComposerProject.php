<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use function str_starts_with;

/**
 * Composer metadata of one project the analysis runs against
 * (an entry of composerAutoloaderProjectPaths).
 */
final class ComposerProject
{

	/**
	 * @param string $rootPath normalized with forward slashes, no trailing slash
	 * @param bool $devInstalled false when Composer ran with --no-dev
	 * @param array<string, ComposerPackage> $packagesByInstallPath install path => package, longest path first
	 */
	public function __construct(
		public string $rootPath,
		public string $vendorDirectory,
		public bool $devInstalled,
		public AutoloadRules $rootAutoload,
		public AutoloadRules $rootAutoloadDev,
		public array $packagesByInstallPath,
	)
	{
	}

	public function findPackageOfDirectory(string $directory): ?ComposerPackage
	{
		foreach ($this->packagesByInstallPath as $installPath => $package) {
			if ($directory === $installPath || str_starts_with($directory, $installPath . '/')) {
				return $package;
			}
		}

		return null;
	}

	public function containsDirectory(string $directory): bool
	{
		return $directory === $this->rootPath || str_starts_with($directory, $this->rootPath . '/');
	}

	public function getAutoloadClassmapPath(): string
	{
		return $this->vendorDirectory . '/composer/autoload_classmap.php';
	}

}
