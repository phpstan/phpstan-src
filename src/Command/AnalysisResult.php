<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;

/** @api */
class AnalysisResult
{

	/** @var \PHPStan\Analyser\Error[] sorted by their file name, line number and message */
	private array $fileSpecificErrors;

	/** @var string[] */
	private array $notFileSpecificErrors;

	/** @var string[] */
	private array $internalErrors;

	/** @var string[] */
	private array $warnings;

	private bool $defaultLevelUsed;

	private ?string $projectConfigFile;

	private bool $savedResultCache;

	/**
	 * @param \PHPStan\Analyser\Error[] $fileSpecificErrors
	 * @param string[] $notFileSpecificErrors
	 * @param string[] $internalErrors
	 * @param string[] $warnings
	 */
	public function __construct(
		array $fileSpecificErrors,
		array $notFileSpecificErrors,
		array $internalErrors,
		array $warnings,
		bool $defaultLevelUsed,
		?string $projectConfigFile,
		bool $savedResultCache
	)
	{
		usort(
			$fileSpecificErrors,
			static function (Error $a, Error $b): int {
				return [
					$a->getFile(),
					$a->getLine(),
					$a->getMessage(),
				] <=> [
					$b->getFile(),
					$b->getLine(),
					$b->getMessage(),
				];
			}
		);

		$this->fileSpecificErrors = $fileSpecificErrors;
		$this->notFileSpecificErrors = $notFileSpecificErrors;
		$this->internalErrors = $internalErrors;
		$this->warnings = $warnings;
		$this->defaultLevelUsed = $defaultLevelUsed;
		$this->projectConfigFile = $projectConfigFile;
		$this->savedResultCache = $savedResultCache;
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
	 * @return \PHPStan\Analyser\Error[] sorted by their file name, line number and message
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
