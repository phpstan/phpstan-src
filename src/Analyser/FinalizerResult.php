<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

final class FinalizerResult
{

	/**
	 * @param list<Error> $collectorErrors
	 * @param list<Error> $locallyIgnoredCollectorErrors
	 * @param list<string> $warnings
	 */
	public function __construct(
		private AnalyserResult $analyserResult,
		private array $collectorErrors,
		private array $locallyIgnoredCollectorErrors,
		private array $warnings = [],
	)
	{
	}

	/**
	 * @return list<Error>
	 */
	public function getErrors(): array
	{
		return $this->analyserResult->getErrors();
	}

	public function getAnalyserResult(): AnalyserResult
	{
		return $this->analyserResult;
	}

	/**
	 * @return list<Error>
	 */
	public function getCollectorErrors(): array
	{
		return $this->collectorErrors;
	}

	/**
	 * @return list<Error>
	 */
	public function getLocallyIgnoredCollectorErrors(): array
	{
		return $this->locallyIgnoredCollectorErrors;
	}

	/**
	 * @return list<string>
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}

}
