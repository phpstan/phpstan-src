<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use function array_filter;
use function array_map;
use function array_values;
use function dirname;
use function str_contains;
use function str_starts_with;

#[AutowiredService(as: StubFilesProvider::class)]
final class DefaultStubFilesProvider implements StubFilesProvider
{

	/** @var string[]|null */
	private ?array $cachedFiles = null;

	/** @var string[]|null */
	private ?array $cachedProjectFiles = null;

	/**
	 * @param string[] $stubFiles
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private Container $container,
		private FileHelper $fileHelper,
		#[AutowiredParameter]
		private array $stubFiles,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
	)
	{
	}

	public function getStubFiles(): array
	{
		if ($this->cachedFiles !== null) {
			return $this->cachedFiles;
		}

		$files = array_map(fn ($path) => $this->fileHelper->normalizePath($path), $this->stubFiles);
		$extensions = $this->container->getServicesByTag(StubFilesExtension::EXTENSION_TAG);
		foreach ($extensions as $extension) {
			foreach ($extension->getFiles() as $extensionFile) {
				$files[] = $this->fileHelper->normalizePath($extensionFile);
			}
		}

		return $this->cachedFiles = $files;
	}

	public function getProjectStubFiles(): array
	{
		if ($this->cachedProjectFiles !== null) {
			return $this->cachedProjectFiles;
		}

		$phpstanStubsDirectory = $this->fileHelper->normalizePath(dirname(dirname(__DIR__)) . '/stubs');

		$filteredStubFiles = $this->getStubFiles();
		$filteredStubFiles = array_filter(
			$filteredStubFiles,
			static fn (string $file): bool => !str_starts_with($file, $phpstanStubsDirectory)
		);
		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$composerConfig = ComposerHelper::getComposerConfig($composerAutoloaderProjectPath);
			if ($composerConfig === null) {
				continue;
			}

			$vendorDir = ComposerHelper::getVendorDirFromComposerConfig($composerAutoloaderProjectPath, $composerConfig);
			$vendorDir = $this->fileHelper->normalizePath($vendorDir);
			$filteredStubFiles = array_filter(
				$filteredStubFiles,
				static fn (string $file): bool => !str_contains($file, $vendorDir),
			);
		}

		return $this->cachedProjectFiles = array_values($filteredStubFiles);
	}

}
