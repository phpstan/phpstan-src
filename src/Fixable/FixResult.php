<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;

/**
 * @api
 */
final class FixResult
{

	/**
	 * @param list<Error> $fixedErrors
	 * @param list<Error> $skippedErrors
	 * @param list<string> $changedFiles
	 */
	public function __construct(
		private array $fixedErrors,
		private array $skippedErrors,
		private array $changedFiles,
	)
	{
	}

	/**
	 * Errors whose fixes were applied. Two errors with the same fix, like an error
	 * in a trait reported for each class using it, are both fixed by one change.
	 *
	 * @return list<Error>
	 */
	public function getFixedErrors(): array
	{
		return $this->fixedErrors;
	}

	/**
	 * Errors whose fixes overlap with the fix of another error or whose files changed since the analysis.
	 *
	 * @return list<Error>
	 */
	public function getSkippedErrors(): array
	{
		return $this->skippedErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getChangedFiles(): array
	{
		return $this->changedFiles;
	}

}
