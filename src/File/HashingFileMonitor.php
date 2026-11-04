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
use function array_values;
use function hash_file;
use function is_dir;
use function is_file;
use function is_readable;

#[AutowiredService]
final class HashingFileMonitor implements FileMonitor
{

	/** Every poll re-hashes every monitored file, so it must stay coarse. */
	private const POLL_INTERVAL_SECONDS = 1.0;

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
		private DirectoryWalker $directoryWalker,
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
			$hash = $this->getFileHash($filePath);
			if ($hash === null) {
				continue;
			}

			$fileHashes[$filePath] = $hash;
		}

		$this->fileHashes = $fileHashes;
		$this->filePaths = $filePaths;
	}

	/**
	 * @param array<string> $filePaths
	 */
	public function watchFiles(array $filePaths): void
	{
		if ($this->fileHashes === null) {
			throw new ShouldNotHappenException();
		}

		foreach ($filePaths as $filePath) {
			if (array_key_exists($filePath, $this->fileHashes)) {
				continue;
			}

			// as it is now, not a change to report
			$hash = $this->getFileHash($filePath);
			if ($hash === null) {
				continue;
			}

			$this->fileHashes[$filePath] = $hash;
		}

		$this->filePaths = $filePaths;
	}

	public function getPollInterval(): float
	{
		return self::POLL_INTERVAL_SECONDS;
	}

	/**
	 * The paths this monitor watches, for a monitor wrapping this one. Includes
	 * the extra files that are missing right now, their appearing is a change too.
	 *
	 * @return array<string>
	 */
	public function getMonitoredFiles(): array
	{
		if ($this->fileHashes === null || $this->filePaths === null) {
			throw new ShouldNotHappenException();
		}

		return array_values(array_unique(array_merge(array_keys($this->fileHashes), $this->filePaths)));
	}

	public function getChanges(): FileMonitorResult
	{
		if ($this->fileHashes === null || $this->filePaths === null) {
			throw new ShouldNotHappenException();
		}

		// Detecting a change means looking at the filesystem as it is now, so the walks shared
		// with the analysis file discovery must not be reused here.
		$this->directoryWalker->clearCachedWalks();
		$finderResult = $this->analyseFileFinder->findFiles($this->analysedPaths);
		$oldFileHashes = $this->fileHashes;
		$fileHashes = [];
		$newFiles = [];
		$changedFiles = [];
		$deletedFiles = [];
		$filePaths = array_unique(array_merge($finderResult->getFiles(), $this->filePaths, $this->getScannedFiles($finderResult->getFiles())));
		foreach ($filePaths as $filePath) {
			// a file that cannot be read is missing, like a config file during a git checkout,
			// it is deleted now and new when it is back
			$newHash = $this->getFileHash($filePath);
			if ($newHash === null) {
				continue;
			}

			if (!array_key_exists($filePath, $oldFileHashes)) {
				$newFiles[] = $filePath;
				$fileHashes[$filePath] = $newHash;
				continue;
			}

			$oldHash = $oldFileHashes[$filePath];
			unset($oldFileHashes[$filePath]);
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

	private function getFileHash(string $filePath): ?string
	{
		if (!is_file($filePath) || !is_readable($filePath)) {
			return null;
		}

		$hash = @hash_file('sha256', $filePath);
		if ($hash === false) {
			return null;
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
