<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Collectors\CollectedData;
use function count;
use function usort;

/** @api */
class AnalysisResult
{

	/** @var list<Error> sorted by their file name, line number and message */
	private array $fileSpecificErrors;

	/**
	 * @param list<Error> $fileSpecificErrors
	 * @param list<string> $notFileSpecificErrors
	 * @param list<string> $internalErrors
	 * @param list<string> $warnings
	 * @param list<CollectedData> $collectedData
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
	 * @return list<string>
	 */
	public function getInternalErrors(): array
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

}
