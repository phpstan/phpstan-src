<?php declare(strict_types=1);

namespace FilterIteratorChild;

class ArchivableFilesFinder extends \FilterIterator
{

	public function __construct()
	{
		parent::__construct(new \ArrayIterator([]));
	}

	public function accept(): bool
	{
		return true;
	}
}

class ArchivableFilesFinderTest
{

	public function doFoo(ArchivableFilesFinder $finder): void
	{

	}

}
