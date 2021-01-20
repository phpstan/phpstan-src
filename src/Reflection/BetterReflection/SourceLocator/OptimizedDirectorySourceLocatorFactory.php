<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\File\FileFinder;

class OptimizedDirectorySourceLocatorFactory
{

	private FileNodesFetcher $fileNodesFetcher;

	private FileFinder $fileFinder;

	public function __construct(FileNodesFetcher $fileNodesFetcher, FileFinder $fileFinder)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileFinder = $fileFinder;
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->fileFinder->findFiles([$directory])->getFiles()
		);
	}

	/**
	 * @param string[] $files
	 * @return OptimizedDirectorySourceLocator
	 */
	public function createByFiles(array $files): OptimizedDirectorySourceLocator
	{
		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$files
		);
	}

}
