<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Parser;
use PHPStan\File\FileFinder;
use PHPStan\Php\PhpVersion;

class OptimizedDirectorySourceLocatorFactory
{

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private FileFinder $fileFinder,
		private PhpVersion $phpVersion,
		private Parser $phpParser,
		private CachingVisitor $cachingVisitor,
	)
	{
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->phpVersion,
			$this->fileFinder->findFiles([$directory])->getFiles(),
			$this->phpParser,
			$this->cachingVisitor,
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
			$this->phpParser,
			$this->cachingVisitor,
		);
	}

}
