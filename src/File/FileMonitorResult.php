<?php declare(strict_types = 1);

namespace PHPStan\File;

class FileMonitorResult
{

	/** @var string[] */
	private $newFiles;

	/** @var string[] */
	private $changedFiles;

	/** @var string[] */
	private $deletedFiles;

	/** @var int */
	private $totalFilesCount;

	/**
	 * @param string[] $newFiles
	 * @param string[] $changedFiles
	 * @param string[] $deletedFiles
	 */
	public function __construct(
		array $newFiles,
		array $changedFiles,
		array $deletedFiles,
		int $totalFilesCount
	)
	{
		$this->newFiles = $newFiles;
		$this->changedFiles = $changedFiles;
		$this->deletedFiles = $deletedFiles;
		$this->totalFilesCount = $totalFilesCount;
	}

	public function hasAnyChanges(): bool
	{
		return count($this->newFiles) > 0
			|| count($this->changedFiles) > 0
			|| count($this->deletedFiles) > 0;
	}

	public function getTotalFilesCount(): int
	{
		return $this->totalFilesCount;
	}

}
