<?php declare(strict_types = 1);

namespace PHPStan\File;

class FileFinderResult
{

	/** @var string[] */
	private array $files;

	private bool $onlyFiles;

	/**
	 * @param string[] $files
	 */
	public function __construct(array $files, bool $onlyFiles)
	{
		$this->files = $files;
		$this->onlyFiles = $onlyFiles;
	}

	/**
	 * @return string[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	public function isOnlyFiles(): bool
	{
		return $this->onlyFiles;
	}

}
