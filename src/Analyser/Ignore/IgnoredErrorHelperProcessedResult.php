<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;

final class IgnoredErrorHelperProcessedResult
{

	/**
	 * @param list<Error> $notIgnoredErrors
	 * @param list<array{Error, mixed[]|string}> $ignoredErrors
	 * @param list<string> $otherIgnoreMessages
	 * @param list<string> $warnings
	 */
	public function __construct(
		private array $notIgnoredErrors,
		private array $ignoredErrors,
		private array $otherIgnoreMessages,
		private array $warnings = [],
	)
	{
	}

	/**
	 * @return list<Error>
	 */
	public function getNotIgnoredErrors(): array
	{
		return $this->notIgnoredErrors;
	}

	/**
	 * @return list<array{Error, mixed[]|string}>
	 */
	public function getIgnoredErrors(): array
	{
		return $this->ignoredErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getOtherIgnoreMessages(): array
	{
		return $this->otherIgnoreMessages;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}

}
