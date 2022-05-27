<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;

class DefaultStubFilesProvider implements StubFilesProvider
{

	/** @var string[]|null */
	private ?array $cachedFiles = null;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		private Container $container,
		private array $stubFiles,
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

}
