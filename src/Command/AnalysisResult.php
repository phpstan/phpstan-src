<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\InternalError;
use PHPStan\Collectors\CollectedData;
use function count;
use function usort;

/**
 * @api
 */
final class AnalysisResult
{

	/** @var list<Error> sorted by their file name, line number and message */
	private array $fileSpecificErrors;

	/**
	 * @param list<Error> $fileSpecificErrors
	 * @param list<string> $notFileSpecificErrors
	 * @param list<InternalError> $internalErrors
	 * @param list<string> $warnings
	 * @param list<CollectedData> $collectedData
	 * @param array<string, string> $changedProjectExtensionFilesOutsideOfAnalysedPaths
	 * @param list<string> $processedFiles
	 */
	public function __construct(
		array $fileSpecificErrors,
		private array $notFileSpecificErrors,
		private array $internalErrors,
		private array $warnings,
		private array $collectedData,
		private bool $defaultLevelUsed,
		private ?string $projectConfigFile,
		private bool $savedResultCache,
		private int $peakMemoryUsageBytes,
		private bool $isResultCacheUsed,
		private array $changedProjectExtensionFilesOutsideOfAnalysedPaths,
		private array $processedFiles = [],
		private bool $resultCacheExisted = true,
	)
	{
		usort(
			$fileSpecificErrors,
			static fn (Error $a, Error $b): int => [
				$a->getFile(),
				$a->getLine(),
				$a->getMessage(),
			] <=> [
				$b->getFile(),
				$b->getLine(),
				$b->getMessage(),
			],
		);

		$this->fileSpecificErrors = $fileSpecificErrors;
	}

	public function hasErrors(): bool
	{
		return $this->getTotalErrorsCount() > 0;
	}

	public function getTotalErrorsCount(): int
	{
		return count($this->fileSpecificErrors) + count($this->notFileSpecificErrors);
	}

	/**
	 * @return list<Error> sorted by their file name, line number and message
	 */
	public function getFileSpecificErrors(): array
	{
		return $this->fileSpecificErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getNotFileSpecificErrors(): array
	{
		return $this->notFileSpecificErrors;
	}

	/**
	 * @return list<InternalError>
	 */
	public function getInternalErrorObjects(): array
	{
		return $this->internalErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}

	public function hasWarnings(): bool
	{
		return count($this->warnings) > 0;
	}

	/**
	 * @return list<CollectedData>
	 */
	public function getCollectedData(): array
	{
		return $this->collectedData;
	}

	public function isDefaultLevelUsed(): bool
	{
		return $this->defaultLevelUsed;
	}

	public function getProjectConfigFile(): ?string
	{
		return $this->projectConfigFile;
	}

	public function hasInternalErrors(): bool
	{
		return count($this->internalErrors) > 0;
	}

	public function isResultCacheSaved(): bool
	{
		return $this->savedResultCache;
	}

	public function getPeakMemoryUsageBytes(): int
	{
		return $this->peakMemoryUsageBytes;
	}

	public function isResultCacheUsed(): bool
	{
		return $this->isResultCacheUsed;
	}

	/**
	 * Whether the result cache file existed when PHPStan started.
	 * False means the cache was never created; a stale/invalid cache that
	 * triggered a full re-analysis still counts as existing.
	 */
	public function resultCacheExisted(): bool
	{
		return $this->resultCacheExisted;
	}

	/**
	 * @return array<string, string>
	 */
	public function getChangedProjectExtensionFilesOutsideOfAnalysedPaths(): array
	{
		return $this->changedProjectExtensionFilesOutsideOfAnalysedPaths;
	}

	/**
	 * @return list<string>
	 */
	public function getProcessedFiles(): array
	{
		return $this->processedFiles;
	}

	/**
	 * @api
	 * @param list<Error> $fileSpecificErrors
	 */
	public function withFileSpecificErrors(array $fileSpecificErrors): self
	{
		return new self(
			$fileSpecificErrors,
			$this->notFileSpecificErrors,
			$this->internalErrors,
			$this->warnings,
			$this->collectedData,
			$this->defaultLevelUsed,
			$this->projectConfigFile,
			$this->savedResultCache,
			$this->peakMemoryUsageBytes,
			$this->isResultCacheUsed,
			$this->changedProjectExtensionFilesOutsideOfAnalysedPaths,
			$this->processedFiles,
			$this->resultCacheExisted,
		);
	}

}
