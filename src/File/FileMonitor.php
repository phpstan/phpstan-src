<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function hash_file;
use function is_dir;
use function is_file;

#[AutowiredService]
final class FileMonitor
{

	/** @var array<string, string>|null */
	private ?array $fileHashes = null;

	/** @var array<string>|null */
	private ?array $filePaths = null;

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 */
	public function __construct(
		#[AutowiredParameter(ref: '@fileFinderAnalyse')]
		private FileFinder $analyseFileFinder,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $scanFileFinder,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private array $scanFiles,
		#[AutowiredParameter]
		private array $scanDirectories,
	)
	{
	}

	/**
	 * @param array<string> $filePaths
	 */
	public function initialize(array $filePaths): void
	{
		$finderResult = $this->analyseFileFinder->findFiles($this->analysedPaths);
		$fileHashes = [];
		foreach (array_unique(array_merge($finderResult->getFiles(), $filePaths, $this->getScannedFiles($finderResult->getFiles()))) as $filePath) {
			$fileHashes[$filePath] = $this->getFileHash($filePath);
		}

		$this->fileHashes = $fileHashes;
		$this->filePaths = $filePaths;
	}

	public function getChanges(): FileMonitorResult
	{
		if ($this->fileHashes === null || $this->filePaths === null) {
			throw new ShouldNotHappenException();
		}
		$finderResult = $this->analyseFileFinder->findFiles($this->analysedPaths);
		$oldFileHashes = $this->fileHashes;
		$fileHashes = [];
		$newFiles = [];
		$changedFiles = [];
		$deletedFiles = [];
		$filePaths = array_unique(array_merge($finderResult->getFiles(), $this->filePaths, $this->getScannedFiles($finderResult->getFiles())));
		foreach ($filePaths as $filePath) {
			if (!array_key_exists($filePath, $oldFileHashes)) {
				$newFiles[] = $filePath;
				$fileHashes[$filePath] = $this->getFileHash($filePath);
				continue;
			}

			$oldHash = $oldFileHashes[$filePath];
			unset($oldFileHashes[$filePath]);
			$newHash = $this->getFileHash($filePath);
			$fileHashes[$filePath] = $newHash;
			if ($oldHash === $newHash) {
				continue;
			}

			$changedFiles[] = $filePath;
		}

		$this->fileHashes = $fileHashes;

		foreach (array_keys($oldFileHashes) as $file) {
			$deletedFiles[] = $file;
		}

		return new FileMonitorResult(
			$newFiles,
			$changedFiles,
			$deletedFiles,
		);
	}

	private function getFileHash(string $filePath): string
	{
		$hash = hash_file('sha256', $filePath);

		if ($hash === false) {
			throw new CouldNotReadFileException($filePath);
		}

		return $hash;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @return array<string>
	 */
	private function getScannedFiles(array $allAnalysedFiles): array
	{
		$scannedFiles = $this->scanFiles;
		$analysedDirectories = [];
		foreach (array_merge($this->analysedPaths, $this->analysedPathsFromConfig) as $analysedPath) {
			if (is_file($analysedPath)) {
				continue;
			}

			if (!is_dir($analysedPath)) {
				continue;
			}

			$analysedDirectories[] = $analysedPath;
		}

		$directories = array_unique(array_merge($analysedDirectories, $this->scanDirectories));
		foreach ($this->scanFileFinder->findFiles($directories)->getFiles() as $file) {
			$scannedFiles[] = $file;
		}

		return array_diff($scannedFiles, $allAnalysedFiles);
	}

}
