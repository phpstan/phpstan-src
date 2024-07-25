<?php declare(strict_types = 1);

namespace PHPStan\File;

use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;

final class FileExcluderFactory
{

	/**
	 * @param string[] $obsoleteExcludesAnalyse
	 * @param array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}|null $excludePaths
	 */
	public function __construct(
		private FileExcluderRawFactory $fileExcluderRawFactory,
		private array $obsoleteExcludesAnalyse,
		private ?array $excludePaths,
	)
	{
	}

	public function createAnalyseFileExcluder(): FileExcluder
	{
		if ($this->excludePaths === null) {
			return $this->fileExcluderRawFactory->create($this->obsoleteExcludesAnalyse);
		}

		$paths = [];
		if (array_key_exists('analyse', $this->excludePaths)) {
			$paths = $this->excludePaths['analyse'];
		}
		if (array_key_exists('analyseAndScan', $this->excludePaths)) {
			$paths = array_merge($paths, $this->excludePaths['analyseAndScan']);
		}

		return $this->fileExcluderRawFactory->create(array_values(array_unique($paths)));
	}

	public function createScanFileExcluder(): FileExcluder
	{
		if ($this->excludePaths === null) {
			return $this->fileExcluderRawFactory->create($this->obsoleteExcludesAnalyse);
		}

		$paths = [];
		if (array_key_exists('analyseAndScan', $this->excludePaths)) {
			$paths = $this->excludePaths['analyseAndScan'];
		}

		return $this->fileExcluderRawFactory->create(array_values(array_unique($paths)));
	}

}
