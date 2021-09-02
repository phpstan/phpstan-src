<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\Parser;
use PHPStan\Type\FileTypeMapper;

class StubPhpDocProviderFactory
{

	private \PHPStan\Parser\Parser $parser;

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	private Container $container;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param \PHPStan\Parser\Parser $parser
	 * @param string[] $stubFiles
	 */
	public function __construct(
		Parser $parser,
		FileTypeMapper $fileTypeMapper,
		Container $container,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->container = $container;
		$this->stubFiles = $stubFiles;
	}

	public function create(): StubPhpDocProvider
	{
		$stubFiles = $this->stubFiles;
		$extensions = $this->container->getServicesByTag(StubFilesExtension::EXTENSION_TAG);
		foreach ($extensions as $extension) {
			$extensionFiles = $extension->getFiles();
			foreach ($extensionFiles as $extensionFile) {
				$stubFiles[] = $extensionFile;
			}
		}

		return new StubPhpDocProvider(
			$this->parser,
			$this->fileTypeMapper,
			$stubFiles
		);
	}

}
