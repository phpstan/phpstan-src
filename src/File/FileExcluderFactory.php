<?php declare(strict_types = 1);

namespace PHPStan\File;

class FileExcluderFactory
{

	private FileExcluderRawFactory $fileExcluderRawFactory;

	/** @var string[] */
	private array $obsoleteExcludesAnalyse;

	/** @var array<int, string>|array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}|null */
	private ?array $excludePaths;

	/**
	 * @param FileExcluderRawFactory $fileExcluderRawFactory
	 * @param string[] $obsoleteExcludesAnalyse
	 * @param array<int, string>|array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}|null $excludePaths
	 */
	public function __construct(
		FileExcluderRawFactory $fileExcluderRawFactory,
		array $obsoleteExcludesAnalyse,
		?array $excludePaths
	)
	{
		$this->fileExcluderRawFactory = $fileExcluderRawFactory;
		$this->obsoleteExcludesAnalyse = $obsoleteExcludesAnalyse;
		$this->excludePaths = $excludePaths;
	}

	public function createAnalyseFileExcluder(): FileExcluder
	{
		if ($this->excludePaths === null) {
			return $this->fileExcluderRawFactory->create($this->obsoleteExcludesAnalyse);
		}

		if (!array_key_exists('analyse', $this->excludePaths) && !array_key_exists('analyseAndScan', $this->excludePaths)) {
			return $this->fileExcluderRawFactory->create($this->excludePaths);
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

		if (!array_key_exists('analyse', $this->excludePaths) && !array_key_exists('analyseAndScan', $this->excludePaths)) {
			return $this->fileExcluderRawFactory->create($this->excludePaths);
		}

		$paths = [];
		if (array_key_exists('analyseAndScan', $this->excludePaths)) {
			$paths = $this->excludePaths['analyseAndScan'];
		}

		return $this->fileExcluderRawFactory->create(array_values(array_unique($paths)));
	}

}
