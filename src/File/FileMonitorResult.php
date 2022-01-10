<?php declare(strict_types = 1);

namespace PHPStan\File;

class FileMonitorResult
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
		private int $totalFilesCount,
	)
	{
	}

	public function hasAnyChanges(): bool
	{
		return $this->newFiles !== []
			|| $this->changedFiles !== []
			|| $this->deletedFiles !== [];
	}

	public function getTotalFilesCount(): int
	{
		return $this->totalFilesCount;
	}

}
