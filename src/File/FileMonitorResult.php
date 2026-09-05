<?php declare(strict_types = 1);

namespace PHPStan\File;

use function count;

final class FileMonitorResult
{

	/**
	 * @param string[] $newFiles
	 * @param string[] $changedFiles
	 * @param string[] $deletedFiles
	 */
	public function __construct(
		private array $newFiles,
		private array $changedFiles,
		private array $deletedFiles,
	)
	{
	}

	/**
	 * @return string[]
	 */
	public function getNewFiles(): array
	{
		return $this->newFiles;
	}

	/**
	 * @return string[]
	 */
	public function getChangedFiles(): array
	{
		return $this->changedFiles;
	}

	/**
	 * @return string[]
	 */
	public function getDeletedFiles(): array
	{
		return $this->deletedFiles;
	}

	public function hasAnyChanges(): bool
	{
		return count($this->newFiles) > 0
			|| count($this->changedFiles) > 0
			|| count($this->deletedFiles) > 0;
	}

}
