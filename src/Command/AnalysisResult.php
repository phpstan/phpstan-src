<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Collectors\CollectedData;
use function count;
use function usort;

/** @api */
class AnalysisResult
{

	/** @var Error[] sorted by their file name, line number and message */
	private array $fileSpecificErrors;

	/**
	 * @param Error[] $fileSpecificErrors
	 * @param string[] $notFileSpecificErrors
	 * @param string[] $internalErrors
	 * @param string[] $warnings
	 * @param CollectedData[] $collectedData
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
	 * @return Error[] sorted by their file name, line number and message
	 */
	public function getFileSpecificErrors(): array
	{
		return $this->fileSpecificErrors;
	}

	/**
	 * @return string[]
	 */
	public function getNotFileSpecificErrors(): array
	{
		return $this->notFileSpecificErrors;
	}

	/**
	 * @return string[]
	 */
	public function getInternalErrors(): array
	{
		return $this->internalErrors;
	}

	/**
	 * @return string[]
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
	 * @return CollectedData[]
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

}
