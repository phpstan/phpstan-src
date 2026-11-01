<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\FinalizerResult;

final class ResultCacheProcessResult
{

	/**
	 * @param Closure(FinalizerResult): bool $saveCallback
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
	 * CollectedDataNode on the merged result first, and save what they found too.
	 */
	public function save(FinalizerResult $finalizerResult): bool
	{
		return ($this->saveCallback)($finalizerResult);
	}

}
