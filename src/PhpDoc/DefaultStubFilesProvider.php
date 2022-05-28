<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Internal\ComposerHelper;
use function array_filter;
use function strpos;

class DefaultStubFilesProvider implements StubFilesProvider
{

	/** @var string[]|null */
	private ?array $cachedFiles = null;

	/** @var string[]|null */
	private ?array $cachedProjectFiles = null;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		private Container $container,
		private array $stubFiles,
		private string $currentWorkingDirectory,
	)
	{
	}

	public function getStubFiles(): array
	{
		if ($this->cachedFiles !== null) {
			return $this->cachedFiles;
		}

		$files = $this->stubFiles;
		$extensions = $this->container->getServicesByTag(StubFilesExtension::EXTENSION_TAG);
		foreach ($extensions as $extension) {
			foreach ($extension->getFiles() as $extensionFile) {
				$files[] = $extensionFile;
			}
		}

		return $this->cachedFiles = $files;
	}

	public function getProjectStubFiles(): array
	{
		if ($this->cachedProjectFiles !== null) {
			return $this->cachedProjectFiles;
		}

		$composerConfig = ComposerHelper::getComposerConfig($this->currentWorkingDirectory);

		if ($composerConfig === null) {
			return $this->getStubFiles();
		}

		return $this->cachedProjectFiles = array_filter(
			$this->getStubFiles(),
			fn (string $file): bool => strpos($file, ComposerHelper::getVendorDirFromComposerConfig($this->currentWorkingDirectory, $composerConfig)) === false
		);
	}

}
