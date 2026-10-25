<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

final class FinalizerResult
{

	/**
	 * @param list<Error> $collectorErrors
	 * @param list<Error> $locallyIgnoredCollectorErrors
	 * @param array<string, CollectedDataRuleResult> $collectedDataRuleResults
	 */
	public function __construct(
		private AnalyserResult $analyserResult,
		private array $collectorErrors,
		private array $locallyIgnoredCollectorErrors,
		private array $collectedDataRuleResults = [],
		private int $reusedRulesCount = 0,
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
	 * @return array<string, CollectedDataRuleResult>
	 */
	public function getCollectedDataRuleResults(): array
	{
		return $this->collectedDataRuleResults;
	}

	/**
	 * How many of the rules on CollectedDataNode reused their errors from the result cache.
	 */
	public function getReusedRulesCount(): int
	{
		return $this->reusedRulesCount;
	}

}
