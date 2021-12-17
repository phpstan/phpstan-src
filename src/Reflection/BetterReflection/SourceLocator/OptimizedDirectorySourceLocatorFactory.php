<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\File\FileFinder;
use PHPStan\Php\PhpVersion;

class OptimizedDirectorySourceLocatorFactory
{

	private FileNodesFetcher $fileNodesFetcher;

	private FileFinder $fileFinder;

	private PhpVersion $phpVersion;

	public function __construct(FileNodesFetcher $fileNodesFetcher, FileFinder $fileFinder, PhpVersion $phpVersion)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileFinder = $fileFinder;
		$this->phpVersion = $phpVersion;
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->phpVersion,
			$this->fileFinder->findFiles([$directory])->getFiles(),
		);
	}

	/**
	 * @param string[] $files
	 */
	public function createByFiles(array $files): OptimizedDirectorySourceLocator
	{
		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->phpVersion,
			$files,
		);
	}

}
