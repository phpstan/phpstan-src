<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\CollectedDataRuleResult;

final class ResultCacheProcessResult
{

	/**
	 * @param Closure(array<string, CollectedDataRuleResult>): bool $saveCallback
	 */
	public function __construct(private AnalyserResult $analyserResult, private Closure $saveCallback)
	{
	}

	public function getAnalyserResult(): AnalyserResult
	{
		return $this->analyserResult;
	}

	/**
	 * Writes the result cache, and says whether it was written.
	 *
	 * It is separate from ResultCacheManager::process() so that the caller can run the rules on
	 * CollectedDataNode on the merged result first, and save their results too.
	 *
	 * @param array<string, CollectedDataRuleResult> $collectedDataRuleResults
	 */
	public function save(array $collectedDataRuleResults): bool
	{
		return ($this->saveCallback)($collectedDataRuleResults);
	}

}
