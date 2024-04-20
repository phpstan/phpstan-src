<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
class LocalIgnoresProcessorResult
{

	/**
	 * @param list<Error> $fileErrors
	 * @param list<Error> $locallyIgnoredErrors
	 * @param LinesToIgnore $linesToIgnore
	 * @param LinesToIgnore $unmatchedLineIgnores
	 */
	public function __construct(
		private array $fileErrors,
		private array $locallyIgnoredErrors,
		private array $linesToIgnore,
		private array $unmatchedLineIgnores,
	)
	{
	}

	/**
	 * @return list<Error>
	 */
	public function getFileErrors(): array
	{
		return $this->fileErrors;
	}

	/**
	 * @return list<Error>
	 */
	public function getLocallyIgnoredErrors(): array
	{
		return $this->locallyIgnoredErrors;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getLinesToIgnore(): array
	{
		return $this->linesToIgnore;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getUnmatchedLineIgnores(): array
	{
		return $this->unmatchedLineIgnores;
	}

}
