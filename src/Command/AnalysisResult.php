<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Error;

class AnalysisResult
{

	/** @var \PHPStan\Analyser\Error[] sorted by their file name, line number and message */
	private $fileSpecificErrors;

	/** @var string[] */
	private $notFileSpecificErrors;

	/** @var string[] */
	private $warnings;

	/** @var bool */
	private $defaultLevelUsed;

	/** @var string|null */
	private $projectConfigFile;

	/**
	 * @param \PHPStan\Analyser\Error[] $fileSpecificErrors
	 * @param string[] $notFileSpecificErrors
	 * @param string[] $warnings
	 * @param bool $defaultLevelUsed
	 * @param string|null $projectConfigFile
	 */
	public function __construct(
		array $fileSpecificErrors,
		array $notFileSpecificErrors,
		array $warnings,
		bool $defaultLevelUsed,
		?string $projectConfigFile
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
		$this->warnings = $warnings;
		$this->defaultLevelUsed = $defaultLevelUsed;
		$this->projectConfigFile = $projectConfigFile;
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

}
